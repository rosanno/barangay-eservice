<?php

namespace App\Jobs;

use App\Models\DocumentRequest;
use App\Services\RequestFlagger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FlagDocumentRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public DocumentRequest $request)
    {
    }

    public function handle(RequestFlagger $flagger): void
    {
        $r = $this->request->fresh();
        $result = $flagger->evaluate($r);

        $r->update([
            'flag_score' => $result['score'],
            'flag_type' => $result['score'] >= RequestFlagger::FLAG_THRESHOLD ? $result['type'] : null,
            'flag_reasons' => $result['reasons'],
            'duplicate_of_id' => $result['duplicateOf'],
            'flagged_at' => $result['score'] >= RequestFlagger::FLAG_THRESHOLD ? now() : null,
        ]);
    }
}