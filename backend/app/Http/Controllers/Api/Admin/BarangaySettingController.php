<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangaySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarangaySettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['data' => BarangaySetting::current()]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'office_hours' => ['nullable', 'string', 'max:150'],
        ]);

        $settings = BarangaySetting::current();
        $settings->update($validated);

        return response()->json(['data' => $settings]);
    }
}