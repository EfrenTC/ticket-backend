<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->categories()->orderBy('name')->get(),
            200
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
        ]);

        $category = $request->user()->categories()->create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6B7280',
            'icon' => $validated['icon'] ?? null,
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, int $categoryId)
    {
        $category = $request->user()->categories()->whereKey($categoryId)->first();

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'color' => 'sometimes|nullable|string|max:20',
            'icon' => 'sometimes|nullable|string|max:50',
        ]);

        $category->update($validated);

        return response()->json($category, 200);
    }

    public function destroy(Request $request, int $categoryId)
    {
        $category = $request->user()->categories()->whereKey($categoryId)->first();

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        if ($category->tickets()->exists()) {
            return response()->json(['message' => 'No se puede eliminar una categoría con tickets asociados'], 422);
        }

        $validated = $request->validate([
            'confirm' => 'required|boolean',
        ]);

        if (!$validated['confirm']) {
            return response()->json(['message' => 'Debes confirmar la eliminación'], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Categoría eliminada'], 200);
    }
}
