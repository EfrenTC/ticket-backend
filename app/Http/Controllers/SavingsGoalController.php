<?php

namespace App\Http\Controllers;

use App\Models\SavingsGoal;
use Illuminate\Http\Request;

class SavingsGoalController extends Controller
{
    public function index(Request $request)
    {
        $goals = $request->user()->savingsGoals()->orderByDesc('created_at')->get();

        return response()->json($goals, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $goal = $request->user()->savingsGoals()->create($validated);

        return response()->json($goal, 201);
    }

    public function update(Request $request, int $goalId)
    {
        $goal = $request->user()->savingsGoals()->whereKey($goalId)->first();

        if (!$goal) {
            return response()->json(['message' => 'Meta no encontrada'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'target_amount' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'is_completed' => 'sometimes|boolean',
        ]);

        $goal->update($validated);

        return response()->json($goal, 200);
    }

    public function destroy(Request $request, int $goalId)
    {
        $goal = $request->user()->savingsGoals()->whereKey($goalId)->first();

        if (!$goal) {
            return response()->json(['message' => 'Meta no encontrada'], 404);
        }

        $validated = $request->validate([
            'confirm' => 'required|boolean',
        ]);

        if (!$validated['confirm']) {
            return response()->json(['message' => 'Debes confirmar la eliminación'], 422);
        }

        $goal->delete();

        return response()->json(['message' => 'Meta eliminada'], 200);
    }

    public function progress(Request $request, int $goalId)
    {
        $goal = $request->user()->savingsGoals()->whereKey($goalId)->first();

        if (!$goal) {
            return response()->json(['message' => 'Meta no encontrada'], 404);
        }

        $spent = $request->user()->tickets()
            ->whereBetween('fecha', [$goal->start_date, $goal->end_date])
            ->sum('importe');

        $savedAmount = max(0, (float) $goal->target_amount - (float) $spent);
        $progress = (float) $goal->target_amount > 0
            ? min(100, round(($savedAmount / (float) $goal->target_amount) * 100, 2))
            : 0;

        return response()->json([
            'goal' => $goal,
            'target_amount' => (float) $goal->target_amount,
            'spent_in_period' => (float) $spent,
            'saved_amount' => $savedAmount,
            'progress_percent' => $progress,
        ], 200);
    }
}
