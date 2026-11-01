<?php

namespace App\Services;

use App\Models\DocumentRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RequestFlagger
{
    const FLAG_THRESHOLD = 50;
    const OPEN_STATUSES = ['pending', 'processing', 'ready_for_pickup'];

    public static function normalize(?string $text): string
    {
        $text = mb_strtolower($text ?? '');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public static function fingerprint(DocumentRequest $r): string
    {
        $details = $r->details ?? [];
        ksort($details);

        return sha1(implode('|', [
            $r->user_id,
            $r->document_type_id,
            self::normalize($r->purpose),
            self::normalize(json_encode($details)),
        ]));
    }

    public function evaluate(DocumentRequest $r): array
    {
        $r->loadMissing('documentType');
        $reasons = [];
        $dupScore = 0;
        $susScore = 0;
        $duplicateOf = null;

        $mine = DocumentRequest::where('user_id', $r->user_id)
            ->where('id', '!=', $r->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $sameType = $mine->where('document_type_id', $r->document_type_id);

        // ---------- Duplicate checks ----------
        $open = $sameType->whereIn('status', self::OPEN_STATUSES)->first();
        if ($open) {
            $dupScore = 80;
            $duplicateOf = $open->id;
            $reasons[] = "An open request for the same document already exists ({$open->tracking_number}, status: {$open->status}).";
        }

        $sameFp = $sameType->firstWhere('fingerprint', $r->fingerprint);
        if ($sameFp && $dupScore < 60) {
            $dupScore = 60;
            $duplicateOf ??= $sameFp->id;
            $reasons[] = "Same document, purpose and details as {$sameFp->tracking_number}.";
        }

        if (!$sameFp) {
            foreach ($sameType as $other) {
                similar_text(self::normalize($r->purpose), self::normalize($other->purpose), $pct);
                if ($pct >= 85) {
                    $dupScore = max($dupScore, 50);
                    $duplicateOf ??= $other->id;
                    $reasons[] = sprintf('Purpose is %d%% similar to %s.', $pct, $other->tracking_number);
                    break;
                }
            }
        }

        // Re-requested right after release (could be a lost copy, so low score; AI decides)
        $recentRelease = $sameType->where('status', 'released')
            ->filter(fn($x) => $x->released_at && $x->released_at->gte(now()->subDays(3)))
            ->first();
        if ($recentRelease && $dupScore < 30) {
            $dupScore = 30;
            $duplicateOf ??= $recentRelease->id;
            $reasons[] = "Same document was released less than 3 days ago ({$recentRelease->tracking_number}).";
        }

        // ---------- Suspicious repeated submissions ----------
        $last24h = $mine->where('created_at', '>=', now()->subDay())->count() + 1;
        if ($last24h >= 6) {
            $susScore = max($susScore, 70);
            $reasons[] = "{$last24h} requests from this account in 24 hours.";
        } elseif ($last24h >= 3) {
            $susScore = max($susScore, 40);
            $reasons[] = "{$last24h} requests from this account in 24 hours.";
        }

        // Many accounts, one IP
        if ($r->ip_address) {
            $ipUsers = DocumentRequest::where('ip_address', $r->ip_address)
                ->where('created_at', '>=', now()->subHour())
                ->distinct()->count('user_id');
            if ($ipUsers >= 5) {
                $susScore = max($susScore, 55);
                $reasons[] = "{$ipUsers} different accounts submitted from this IP in the last hour.";
            }
        }

        // Same fingerprint across accounts is a strong abuse signal
        $crossAccount = DocumentRequest::where('fingerprint', $r->fingerprint)
            ->where('user_id', '!=', $r->user_id)
            ->where('created_at', '>=', now()->subDays(7))->exists();
        if ($crossAccount) {
            $susScore = max($susScore, 45);
            $reasons[] = 'Identical request content was submitted by another account recently.';
        }

        // Rejected / cancelled, then resubmitted
        $bounced = $sameType->whereIn('status', ['rejected', 'cancelled'])
            ->where('created_at', '>=', now()->subDays(7))->count();
        if ($bounced >= 2) {
            $susScore = max($susScore, 35);
            $reasons[] = "Resubmitted after {$bounced} rejected or cancelled requests this week.";
        }

        $score = min(100, max($dupScore, $susScore) + (($dupScore >= 50 && $susScore >= 35) ? 15 : 0));
        $type = $dupScore >= $susScore ? 'duplicate' : 'suspicious';

        $result = compact('score', 'type', 'reasons', 'duplicateOf');

        // ---------- AI second opinion for borderline scores ----------
        if ($score >= 30 && $score <= 70) {
            $result = $this->aiReview($r, $mine, $result);
        }

        return $result;
    }

    protected function aiReview(DocumentRequest $r, $history, array $result): array
    {
        $key = config('services.anthropic.key');
        if (!$key)
            return $result;

        $recent = $history->sortByDesc('created_at')->take(10)->map(fn($h) => [
            'tracking' => $h->tracking_number,
            'document_type_id' => $h->document_type_id,
            'purpose' => $h->purpose,
            'status' => $h->status,
            'submitted' => $h->created_at->toDateTimeString(),
        ])->values();

        $prompt = "A resident submitted a barangay document request. Decide whether it is a likely "
            . "duplicate or a suspicious repeated submission. Legitimate cases exist (a different purpose "
            . "such as employment vs. scholarship, a lost copy, a corrected request after rejection). "
            . "Purposes may be in Filipino or English.\n\n"
            . 'NEW REQUEST: ' . json_encode([
                'document' => $r->documentType->name ?? $r->document_type_id,
                'document_type_id' => $r->document_type_id,
                'purpose' => $r->purpose,
                'details' => $r->details,
            ]) . "\n"
            . 'RECENT HISTORY (same user, last 30 days): ' . json_encode($recent) . "\n"
            . 'RULE FINDINGS: ' . json_encode($result['reasons']) . "\n\n"
            . 'Reply with JSON only: {"flag": true|false, "type": "duplicate"|"suspicious", "confidence": 0-100, "reason": "one short sentence"}';

        try {
            $res = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
            ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
                        'model' => config('services.anthropic.model', 'claude-haiku-5-5'),
                        'max_tokens' => 300,
                        'messages' => [['role' => 'user', 'content' => $prompt]],
                    ]);

            $text = preg_replace('/```json|```/', '', $res->json('content.0.text', ''));
            $ai = json_decode(trim($text), true);

            if (is_array($ai) && isset($ai['flag'])) {
                $result['reasons'][] = 'AI review: ' . ($ai['reason'] ?? '');
                if ($ai['flag']) {
                    $result['score'] = max($result['score'], min(100, (int) ($ai['confidence'] ?? 60)));
                    $result['type'] = in_array($ai['type'] ?? '', ['duplicate', 'suspicious']) ? $ai['type'] : $result['type'];
                } else {
                    $result['score'] = min($result['score'], self::FLAG_THRESHOLD - 1);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AI flag review failed: ' . $e->getMessage()); // falls back to rules only
        }

        return $result;
    }
}