<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $budgets = $request->user()
            ->budgets()
            ->with('category')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return response()->json($budgets, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        if (!empty($validated['category_id'])) {
            $isOwned = $request->user()->categories()->whereKey($validated['category_id'])->exists();
            if (!$isOwned) {
                return response()->json(['message' => 'Categoría no válida para este usuario'], 422);
            }
        }

        $budget = Budget::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'category_id' => $validated['category_id'] ?? null,
                'month' => $validated['month'],
                'year' => $validated['year'],
            ],
            ['amount' => $validated['amount']]
        );

        return response()->json($budget->load('category'), 201);
    }

    public function update(Request $request, int $budgetId)
    {
        $budget = $request->user()->budgets()->whereKey($budgetId)->first();

        if (!$budget) {
            return response()->json(['message' => 'Presupuesto no encontrado'], 404);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'month' => 'sometimes|integer|min:1|max:12',
            'year' => 'sometimes|integer|min:2000|max:2100',
            'category_id' => 'sometimes|nullable|integer|exists:categories,id',
        ]);

        if (array_key_exists('category_id', $validated) && !empty($validated['category_id'])) {
            $isOwned = $request->user()->categories()->whereKey($validated['category_id'])->exists();
            if (!$isOwned) {
                return response()->json(['message' => 'Categoría no válida para este usuario'], 422);
            }
        }

        $budget->update($validated);

        return response()->json($budget->load('category'), 200);
    }

    public function destroy(Request $request, int $budgetId)
    {
        $budget = $request->user()->budgets()->whereKey($budgetId)->first();

        if (!$budget) {
            return response()->json(['message' => 'Presupuesto no encontrado'], 404);
        }

        $validated = $request->validate([
            'confirm' => 'required|boolean',
        ]);

        if (!$validated['confirm']) {
            return response()->json(['message' => 'Debes confirmar la eliminación'], 422);
        }

        $budget->delete();

        return response()->json(['message' => 'Presupuesto eliminado'], 200);
    }

    public function alerts(Request $request)
    {
        $now = Carbon::now();

        $budgets = $request->user()->budgets()
            ->with('category')
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->get();

        $alerts = $budgets->map(function (Budget $budget) use ($request, $now) {
            $tickets = $request->user()->tickets()
                ->whereMonth('fecha', $now->month)
                ->whereYear('fecha', $now->year)
                ->when($budget->category_id, function ($query) use ($budget) {
                    $query->where('category_id', $budget->category_id);
                })
                ->sum('importe');

            $ratio = $budget->amount > 0 ? ((float) $tickets / (float) $budget->amount) : 0;

            return [
                'budget_id' => $budget->id,
                'category_id' => $budget->category_id,
                'category_name' => $budget->category?->name,
                'budget' => (float) $budget->amount,
                'spent' => (float) $tickets,
                'ratio' => round($ratio, 2),
                'status' => $ratio >= 1 ? 'exceeded' : ($ratio >= 0.8 ? 'near_limit' : 'ok'),
            ];
        });

        return response()->json($alerts, 200);
    }
}
