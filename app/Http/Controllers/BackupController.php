<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function export(Request $request)
    {
        $user = $request->user()->load([
            'categories',
            'tags',
            'budgets',
            'savingsGoals',
            'dashboardWidgets',
            'reminders',
            'tickets.tags',
            'tickets.category',
        ]);

        $backup = [
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'currency' => $user->currency,
                'date_format' => $user->date_format,
                'language' => $user->language,
                'dark_mode' => $user->dark_mode,
                'report_frequency' => $user->report_frequency,
                'budget_alerts_enabled' => $user->budget_alerts_enabled,
            ],
            'categories' => $user->categories,
            'tags' => $user->tags,
            'budgets' => $user->budgets,
            'savings_goals' => $user->savingsGoals,
            'dashboard_widgets' => $user->dashboardWidgets,
            'reminders' => $user->reminders,
            'tickets' => $user->tickets,
        ];

        $content = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($content, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename=ticket_manager_backup.json',
        ]);
    }

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:json|max:20480',
            'confirm' => 'required|boolean',
        ]);

        if (!$validated['confirm']) {
            return response()->json(['message' => 'Debes confirmar la restauración'], 422);
        }

        $content = file_get_contents($validated['file']->getRealPath());
        $data = json_decode((string) $content, true);

        if (!is_array($data)) {
            return response()->json(['message' => 'Formato JSON inválido'], 422);
        }

        DB::transaction(function () use ($request, $data) {
            $user = $request->user();

            $user->tickets()->delete();
            $user->categories()->delete();
            $user->tags()->delete();
            $user->budgets()->delete();
            $user->savingsGoals()->delete();
            $user->dashboardWidgets()->delete();
            $user->reminders()->delete();

            $categoryMap = [];
            foreach (($data['categories'] ?? []) as $category) {
                $new = $user->categories()->create([
                    'name' => $category['name'] ?? 'Sin nombre',
                    'color' => $category['color'] ?? '#6B7280',
                    'icon' => $category['icon'] ?? null,
                ]);
                $categoryMap[$category['id']] = $new->id;
            }

            $tagMap = [];
            foreach (($data['tags'] ?? []) as $tag) {
                $new = $user->tags()->create([
                    'name' => $tag['name'] ?? 'Sin nombre',
                ]);
                $tagMap[$tag['id']] = $new->id;
            }

            foreach (($data['tickets'] ?? []) as $ticket) {
                $newTicket = $user->tickets()->create([
                    'gasto' => $ticket['gasto'] ?? 'Sin descripción',
                    'importe' => $ticket['importe'] ?? 0,
                    'categoria' => $ticket['categoria'] ?? 'Otros',
                    'category_id' => isset($ticket['category_id']) ? ($categoryMap[$ticket['category_id']] ?? null) : null,
                    'cif' => $ticket['cif'] ?? 'N/A',
                    'metodo_pago' => $ticket['metodo_pago'] ?? 'Otros',
                    'conciliado' => $ticket['conciliado'] ?? 'pendiente',
                    'conciliado_en' => $ticket['conciliado_en'] ?? null,
                    'referencia_bancaria' => $ticket['referencia_bancaria'] ?? null,
                    'fecha' => $ticket['fecha'] ?? now()->toDateString(),
                ]);

                $tagIds = collect($ticket['tags'] ?? [])
                    ->pluck('id')
                    ->map(fn ($id) => $tagMap[$id] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $newTicket->tags()->sync($tagIds);
            }

            foreach (($data['budgets'] ?? []) as $budget) {
                $user->budgets()->create([
                    'category_id' => isset($budget['category_id']) ? ($categoryMap[$budget['category_id']] ?? null) : null,
                    'amount' => $budget['amount'] ?? 0,
                    'month' => $budget['month'] ?? now()->month,
                    'year' => $budget['year'] ?? now()->year,
                ]);
            }

            foreach (($data['savings_goals'] ?? []) as $goal) {
                $user->savingsGoals()->create([
                    'title' => $goal['title'] ?? 'Meta',
                    'target_amount' => $goal['target_amount'] ?? 0,
                    'start_date' => $goal['start_date'] ?? now()->toDateString(),
                    'end_date' => $goal['end_date'] ?? now()->toDateString(),
                    'is_completed' => $goal['is_completed'] ?? false,
                ]);
            }

            foreach (($data['dashboard_widgets'] ?? []) as $widget) {
                $user->dashboardWidgets()->create([
                    'widget_key' => $widget['widget_key'] ?? 'default_widget',
                    'position' => $widget['position'] ?? 0,
                    'enabled' => $widget['enabled'] ?? true,
                    'settings' => $widget['settings'] ?? null,
                ]);
            }

            foreach (($data['reminders'] ?? []) as $reminder) {
                $user->reminders()->create([
                    'type' => $reminder['type'] ?? 'in_app',
                    'title' => $reminder['title'] ?? 'Recordatorio',
                    'message' => $reminder['message'] ?? '',
                    'send_at' => $reminder['send_at'] ?? null,
                    'read_at' => $reminder['read_at'] ?? null,
                ]);
            }

            $profile = $data['user'] ?? [];
            $user->update([
                'name' => $profile['name'] ?? $user->name,
                'email' => $profile['email'] ?? $user->email,
                'avatar_url' => $profile['avatar_url'] ?? null,
                'currency' => $profile['currency'] ?? 'EUR',
                'date_format' => $profile['date_format'] ?? 'd/m/Y',
                'language' => $profile['language'] ?? 'es',
                'dark_mode' => $profile['dark_mode'] ?? false,
                'report_frequency' => $profile['report_frequency'] ?? 'none',
                'budget_alerts_enabled' => $profile['budget_alerts_enabled'] ?? true,
            ]);
        });

        return response()->json(['message' => 'Restauración completada'], 200);
    }
}
