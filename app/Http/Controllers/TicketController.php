<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index()
    {
        return response()->json(Ticket::all(), 200);
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

        $ticket = Ticket::create($validated);
        return response()->json($ticket, 201);
    }


    public function show(Ticket $ticket)
    {
        return response()->json($ticket, 200);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'gasto'       => 'sometimes|string|max:255',
            'importe'     => 'sometimes|numeric',
            'categoria'   => 'sometimes|in:Restauración,Aparcamiento,Peaje,Transporte,Alojamiento,Gasolina,Otros',
            'conciliado'  => 'sometimes|in:pendiente,terminado',
        ]);

        $ticket->update($validated);
        return response()->json($ticket, 200);
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        return response()->json(['message' => 'Ticket eliminado'], 200);
    }
}
