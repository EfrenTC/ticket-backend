<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
            'category_id' => 'nullable|integer',
            'categoria' => 'nullable|string|max:255',
            'metodo_pago' => 'nullable|string|max:50',
            'conciliado' => 'nullable|in:pendiente,terminado',
            'tag' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|in:fecha,importe,created_at',
            'sort_dir' => 'nullable|in:asc,desc',
        ]);

        $query = $request->user()->tickets()->with(['category', 'tags']);

        if (!empty($validated['q'])) {
            $search = $validated['q'];
            $query->where(function ($builder) use ($search) {
                $builder->where('gasto', 'like', "%{$search}%")
                    ->orWhere('cif', 'like', "%{$search}%")
                    ->orWhere('categoria', 'like', "%{$search}%")
                    ->orWhere('referencia_bancaria', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['fecha_desde'])) {
            $query->whereDate('fecha', '>=', $validated['fecha_desde']);
        }

        if (!empty($validated['fecha_hasta'])) {
            $query->whereDate('fecha', '<=', $validated['fecha_hasta']);
        }

        if (!empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (!empty($validated['categoria'])) {
            $query->where('categoria', $validated['categoria']);
        }

        if (!empty($validated['metodo_pago'])) {
            $query->where('metodo_pago', $validated['metodo_pago']);
        }

        if (!empty($validated['conciliado'])) {
            $query->where('conciliado', $validated['conciliado']);
        }

        if (!empty($validated['tag'])) {
            $tag = $validated['tag'];
            $query->whereHas('tags', function ($builder) use ($tag) {
                $builder->where('name', 'like', "%{$tag}%");
            });
        }

        $sortBy = $validated['sort_by'] ?? 'fecha';
        $sortDir = $validated['sort_dir'] ?? 'desc';

        $tickets = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($tickets, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gasto' => 'required|string|max:255',
            'importe' => 'required|numeric',
            'categoria' => 'nullable|in:Restauración,Aparcamiento,Peaje,Transporte,Alojamiento,Gasolina,Otros',
            'category_id' => 'nullable|integer|exists:categories,id',
            'cif' => 'required|string|max:20',
            'metodo_pago' => 'required|in:Efectivo,Tarjeta,Otros',
            'conciliado' => 'required|in:pendiente,terminado',
            'fecha' => 'required|date',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        $category = null;
        if (!empty($validated['category_id'])) {
            $category = $request->user()->categories()->whereKey($validated['category_id'])->first();
            if (!$category) {
                return response()->json(['message' => 'Categoría no válida para este usuario'], 422);
            }
        }

        $ticketData = [
            'gasto' => $validated['gasto'],
            'importe' => $validated['importe'],
            'categoria' => $validated['categoria'] ?? 'Otros',
            'category_id' => $validated['category_id'] ?? null,
            'cif' => $validated['cif'],
            'metodo_pago' => $validated['metodo_pago'],
            'conciliado' => $validated['conciliado'],
            'conciliado_en' => ($validated['conciliado'] ?? null) === 'terminado' ? Carbon::now() : null,
            'fecha' => $validated['fecha'],
        ];

        if ($category) {
            $ticketData['categoria'] = in_array($category->name, [
                'Restauración',
                'Aparcamiento',
                'Peaje',
                'Transporte',
                'Alojamiento',
                'Gasolina',
                'Otros',
            ], true) ? $category->name : 'Otros';
        }

        $ticket = $request->user()->tickets()->create($ticketData);

        if (!empty($validated['tag_ids'])) {
            $tagIds = $request->user()->tags()->whereIn('id', $validated['tag_ids'])->pluck('id')->all();
            $ticket->tags()->sync($tagIds);
        }

        return response()->json($ticket->load(['category', 'tags']), 201);
    }

    public function show(Request $request, Ticket $ticket)
    {
        if ($request->user()->id !== $ticket->user_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($ticket->load(['category', 'tags']), 200);
    }

    public function update(Request $request, Ticket $ticket)
    {
        if ($request->user()->id !== $ticket->user_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'gasto' => 'sometimes|string|max:255',
            'importe' => 'sometimes|numeric',
            'categoria' => 'sometimes|in:Restauración,Aparcamiento,Peaje,Transporte,Alojamiento,Gasolina,Otros',
            'category_id' => 'sometimes|nullable|integer|exists:categories,id',
            'cif' => 'sometimes|string|max:20',
            'metodo_pago' => 'sometimes|in:Efectivo,Tarjeta,Otros',
            'conciliado' => 'sometimes|in:pendiente,terminado',
            'fecha' => 'sometimes|date',
            'referencia_bancaria' => 'sometimes|nullable|string|max:255',
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        if (array_key_exists('category_id', $validated) && !empty($validated['category_id'])) {
            $belongsToUser = $request->user()->categories()->whereKey($validated['category_id'])->exists();
            if (!$belongsToUser) {
                return response()->json(['message' => 'Categoría no válida para este usuario'], 422);
            }
        }

        if (($validated['conciliado'] ?? null) === 'terminado') {
            $validated['conciliado_en'] = $ticket->conciliado_en ?? Carbon::now();
        }

        if (($validated['conciliado'] ?? null) === 'pendiente') {
            $validated['conciliado_en'] = null;
            $validated['referencia_bancaria'] = null;
        }

        $ticket->update($validated);

        if (array_key_exists('tag_ids', $validated)) {
            $tagIds = $request->user()->tags()->whereIn('id', $validated['tag_ids'])->pluck('id')->all();
            $ticket->tags()->sync($tagIds);
        }

        return response()->json($ticket->load(['category', 'tags']), 200);
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        if ($request->user()->id !== $ticket->user_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'confirm' => 'required|boolean',
        ]);

        if (!$validated['confirm']) {
            return response()->json(['message' => 'Debes confirmar la eliminación'], 422);
        }

        $ticket->delete();

        return response()->json(['message' => 'Ticket eliminado'], 200);
    }
}
