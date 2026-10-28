<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AiService;

class AiController extends Controller
{
    public function chat(Request $r, AiService $ai)
    {
        $data = $r->validate([
            'messages' => 'required|array|max:20',
            'messages.*.role' => 'in:user,assistant',
            'messages.*.content' => 'required|string|max:1000',
        ]);
        return response()->json(['data' => ['reply' => $ai->assist($data['messages'], app()->getLocale())]]);
    }

    public function translate(Request $r, AiService $ai)
    {
        $d = $r->validate(['text' => 'required|string|max:2000', 'target' => 'required|in:en,fil']);
        return response()->json(['data' => ['text' => $ai->translate($d['text'], $d['target'])]]);
    }
}
