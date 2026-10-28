<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    /**
     * Register services.
     */
    private function call(string $system, array $messages, int $max = 800): string
    {
        // Gemini uses "model" instead of "assistant" and wraps text in "parts"
        $contents = collect($messages)->map(fn($m) => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ])->values()->all();

        $model = config('services.gemini.model');

        $res = Http::withHeaders([
            'x-goog-api-key' => config('services.gemini.key'),
        ])->timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'system_instruction' => ['parts' => [['text' => $system]]],
                    'contents' => $contents,
                    'generationConfig' => ['maxOutputTokens' => $max],
                ]
            )->throw();

        return $res->json('candidates.0.content.parts.0.text') ?? '';
    }

    /** Resident-facing Q&A in English or Filipino (incl. Taglish) */
    public function assist(array $history, string $uiLocale): string
    {
        $docs = config('barangay.documents'); // requirements, fees, processing days
        $system = <<<SYS
You are the virtual assistant for a Philippine barangay's online document request system.
Reply in the SAME language the resident writes in (English, Filipino, or Taglish). If unclear, use "{$uiLocale}".
Use plain, polite, simple Filipino/English. Use "po" and "opo" appropriately in Filipino replies.
Only answer using the document catalog below. If asked about something not covered
(legal advice, case status, fees not listed), say you are unsure and advise visiting the barangay hall.
Never invent requirements, fees, or schedules. Never ask for or repeat IDs or passwords.

DOCUMENT CATALOG (JSON):
{$docs}
SYS;

        return $this->call($system, $history);
    }

    /** Translate free text (purpose, admin remarks) between en <-> fil */
    public function translate(string $text, string $target): string
    {
        $lang = $target === 'fil' ? 'Filipino (Tagalog)' : 'English';
        $system = "Translate the user's text into {$lang}. Keep names, addresses, numbers, and document names unchanged. Output only the translation.";
        return $this->call($system, [['role' => 'user', 'content' => $text]], 500);
    }
}
