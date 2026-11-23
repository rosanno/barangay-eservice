<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purok;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurokController extends Controller
{
    public function index(Request $request)
    {
        $puroks = Purok::query()
            ->withCount(['households', 'members'])
            ->when($request->search, fn($q, $s) =>
                $q->where(fn($w) => $w->where('name', 'like', "%$s%")
                    ->orWhere('leader_name', 'like', "%$s%")))
            ->when($request->filled('is_active'), fn($q) =>
                $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $puroks]);
    }

    public function show(Purok $purok)
    {
        $purok->loadCount(['households', 'members']);
        return response()->json(['data' => $purok]);
    }

    public function store(Request $request)
    {
        $purok = Purok::create($this->validated($request));
        return response()->json(['data' => $purok], 201);
    }

    public function update(Request $request, Purok $purok)
    {
        $purok->update($this->validated($request, $purok->id));
        return response()->json(['data' => $purok->fresh()]);
    }

    public function destroy(Purok $purok)
    {
        if ($purok->households()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a purok that still has households. Reassign them first.',
            ], 422);
        }
        $purok->delete();
        return response()->json(['message' => 'Purok deleted.']);
    }

    private function validated(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('puroks', 'name')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:255'],
            'leader_name' => ['nullable', 'string', 'max:150'],
            'leader_contact' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);
    }
}