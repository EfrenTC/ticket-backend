<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $reminders = $request->user()->reminders()->latest()->paginate(20);

        return response()->json($reminders, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'nullable|integer|exists:tickets,id',
            'type' => 'nullable|in:in_app,email',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'send_at' => 'nullable|date',
        ]);

        if (!empty($validated['ticket_id'])) {
            $ownedTicket = $request->user()->tickets()->whereKey($validated['ticket_id'])->exists();
            if (!$ownedTicket) {
                return response()->json(['message' => 'Ticket no válido para este usuario'], 422);
            }
        }

        $reminder = $request->user()->reminders()->create($validated);

        return response()->json($reminder, 201);
    }

    public function markAsRead(Request $request, int $reminderId)
    {
        $reminder = $request->user()->reminders()->whereKey($reminderId)->first();

        if (!$reminder) {
            return response()->json(['message' => 'Recordatorio no encontrado'], 404);
        }

        $reminder->update(['read_at' => now()]);

        return response()->json($reminder, 200);
    }

    public function pendingTicketsSummary(Request $request)
    {
        $pendingCount = $request->user()->tickets()->where('conciliado', 'pendiente')->count();

        return response()->json([
            'pending_tickets' => $pendingCount,
            'message' => $pendingCount > 0
                ? 'Tienes tickets pendientes de conciliación'
                : 'No tienes tickets pendientes',
        ], 200);
    }
}
