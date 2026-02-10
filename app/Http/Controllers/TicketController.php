<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{


public function index(Request $request)
{
    return response()->json($request->user()->tickets, 200);
}

public function store(Request $request)
{
    $validated = $request->validate([
        'gasto'       => 'required|string|max:255',
        'importe'     => 'required|numeric',
        'categoria'   => 'required|in:Restauración,Aparcamiento,Peaje,Transporte,Alojamiento,Gasolina,Otros',
        'cif'         => 'required|string|max:20',
        'metodo_pago' => 'required|in:Efectivo,Tarjeta,Otros',
        'conciliado'  => 'required|in:pendiente,terminado',
        'fecha'       => 'required|date',
    ]);

    $ticket = $request->user()->tickets()->create($validated);
    
    return response()->json($ticket, 201);
}

public function update(Request $request, Ticket $ticket)
{
    if ($request->user()->id !== $ticket->user_id) {
        return response()->json(['message' => 'No autorizado'], 403);
    }

    $validated = $request->validate([
        'gasto'       => 'sometimes|string|max:255',
        'importe'     => 'sometimes|numeric',
        'categoria'   => 'sometimes|in:Restauración,Aparcamiento,Peaje,Transporte,Alojamiento,Gasolina,Otros',
        'cif'         => 'sometimes|string|max:20',
        'metodo_pago' => 'sometimes|in:Efectivo,Tarjeta,Otros',
        'conciliado'  => 'sometimes|in:pendiente,terminado',
        'fecha'       => 'sometimes|date',
    ]);

    $ticket->update($validated);
    return response()->json($ticket, 200);
}

public function destroy(Request $request, Ticket $ticket)
{
    if ($request->user()->id !== $ticket->user_id) {
        return response()->json(['message' => 'No autorizado'], 403);
    }
    
    $ticket->delete();
    return response()->json(['message' => 'Ticket eliminado'], 200);
}
}
