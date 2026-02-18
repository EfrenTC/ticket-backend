<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function export(Request $request)
    {
        $validated = $request->validate([
            'format' => 'nullable|in:csv,xls',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $format = $validated['format'] ?? 'csv';
        $month = $validated['month'] ?? Carbon::now()->month;
        $year = $validated['year'] ?? Carbon::now()->year;

        $tickets = $request->user()->tickets()
            ->with(['category', 'tags'])
            ->whereMonth('fecha', $month)
            ->whereYear('fecha', $year)
            ->orderBy('fecha')
            ->get();

        $separator = $format === 'xls' ? "\t" : ',';
        $lines = [];
        $lines[] = implode($separator, ['id', 'fecha', 'gasto', 'importe', 'categoria', 'metodo_pago', 'conciliado', 'tags']);

        foreach ($tickets as $ticket) {
            $lines[] = implode($separator, [
                $ticket->id,
                $ticket->fecha?->format('Y-m-d'),
                str_replace(["\n", "\r", $separator], ' ', $ticket->gasto),
                $ticket->importe,
                $ticket->category?->name ?? $ticket->categoria,
                $ticket->metodo_pago,
                $ticket->conciliado,
                $ticket->tags->pluck('name')->implode('|'),
            ]);
        }

        $content = implode("\n", $lines);
        $filename = sprintf('tickets_%d_%02d.%s', $year, $month, $format === 'xls' ? 'xls' : 'csv');

        return response($content, 200, [
            'Content-Type' => $format === 'xls' ? 'application/vnd.ms-excel' : 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function compare(Request $request)
    {
        $now = Carbon::now();
        $currentMonthTotal = $request->user()->tickets()
            ->whereMonth('fecha', $now->month)
            ->whereYear('fecha', $now->year)
            ->sum('importe');

        $previous = $now->copy()->subMonth();
        $previousMonthTotal = $request->user()->tickets()
            ->whereMonth('fecha', $previous->month)
            ->whereYear('fecha', $previous->year)
            ->sum('importe');

        $sameMonthLastYear = $request->user()->tickets()
            ->whereMonth('fecha', $now->month)
            ->whereYear('fecha', $now->year - 1)
            ->sum('importe');

        return response()->json([
            'current_month' => (float) $currentMonthTotal,
            'previous_month' => (float) $previousMonthTotal,
            'same_month_last_year' => (float) $sameMonthLastYear,
            'variation_vs_previous_month' => (float) $currentMonthTotal - (float) $previousMonthTotal,
            'variation_vs_last_year' => (float) $currentMonthTotal - (float) $sameMonthLastYear,
        ], 200);
    }

    public function charts(Request $request)
    {
        $validated = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $month = $validated['month'] ?? Carbon::now()->month;
        $year = $validated['year'] ?? Carbon::now()->year;

        $base = $request->user()->tickets()
            ->whereMonth('fecha', $month)
            ->whereYear('fecha', $year)
            ->get();

        $byCategory = $base->groupBy(function ($ticket) {
            return $ticket->category?->name ?? $ticket->categoria ?? 'Sin categoría';
        })->map(fn ($items) => round((float) $items->sum('importe'), 2));

        $byWeekday = $base->groupBy(function ($ticket) {
            return $ticket->fecha?->locale('es')->translatedFormat('l') ?? 'N/A';
        })->map(fn ($items) => round((float) $items->sum('importe'), 2));

        $daily = $base->groupBy(function ($ticket) {
            return $ticket->fecha?->format('Y-m-d') ?? 'N/A';
        })->map(fn ($items) => round((float) $items->sum('importe'), 2));

        return response()->json([
            'month' => $month,
            'year' => $year,
            'evolution_by_category' => $byCategory,
            'expenses_by_weekday' => $byWeekday,
            'daily_evolution' => $daily,
        ], 200);
    }

    public function calendar(Request $request)
    {
        $validated = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $month = $validated['month'] ?? Carbon::now()->month;
        $year = $validated['year'] ?? Carbon::now()->year;

        $tickets = $request->user()->tickets()
            ->with(['category', 'tags'])
            ->whereMonth('fecha', $month)
            ->whereYear('fecha', $year)
            ->orderBy('fecha')
            ->get()
            ->groupBy(fn ($ticket) => $ticket->fecha?->format('Y-m-d'));

        return response()->json([
            'month' => $month,
            'year' => $year,
            'days' => $tickets,
        ], 200);
    }

    public function weeklyOrMonthlySummary(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|in:weekly,monthly',
        ]);

        $now = Carbon::now();

        if ($validated['period'] === 'weekly') {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
        } else {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
        }

        $tickets = $request->user()->tickets()
            ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
            ->get();

        return response()->json([
            'period' => $validated['period'],
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'total_tickets' => $tickets->count(),
            'total_spent' => (float) $tickets->sum('importe'),
            'pending_reconciliation' => $tickets->where('conciliado', 'pendiente')->count(),
            'by_payment_method' => $tickets->groupBy('metodo_pago')->map->count(),
        ], 200);
    }
}
