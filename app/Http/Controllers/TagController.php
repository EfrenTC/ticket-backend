<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->tags()->orderBy('name')->get(), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $tag = $request->user()->tags()->create($validated);

        return response()->json($tag, 201);
    }

    public function update(Request $request, int $tagId)
    {
        $tag = $request->user()->tags()->whereKey($tagId)->first();

        if (!$tag) {
            return response()->json(['message' => 'Etiqueta no encontrada'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $tag->update($validated);

        return response()->json($tag, 200);
    }

    public function destroy(Request $request, int $tagId)
    {
        $tag = $request->user()->tags()->whereKey($tagId)->first();

        if (!$tag) {
            return response()->json(['message' => 'Etiqueta no encontrada'], 404);
        }

        $validated = $request->validate([
            'confirm' => 'required|boolean',
        ]);

        if (!$validated['confirm']) {
            return response()->json(['message' => 'Debes confirmar la eliminación'], 422);
        }

        $tag->delete();

        return response()->json(['message' => 'Etiqueta eliminada'], 200);
    }
}
