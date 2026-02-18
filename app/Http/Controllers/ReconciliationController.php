<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReconciliationController extends Controller
{
    public function bulkMarkReconciled(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|array|min:1',
            'ticket_ids.*' => 'integer|exists:tickets,id',
            'confirm' => 'required|boolean',
        ]);

        if (!$validated['confirm']) {
            return response()->json(['message' => 'Debes confirmar la conciliación masiva'], 422);
        }

        $updated = $request->user()->tickets()
            ->whereIn('id', $validated['ticket_ids'])
            ->update([
                'conciliado' => 'terminado',
                'conciliado_en' => Carbon::now(),
            ]);

        return response()->json([
            'message' => 'Conciliación masiva completada',
            'updated_count' => $updated,
        ], 200);
    }

    public function importBankCsv(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'date_column' => 'nullable|string|max:100',
            'amount_column' => 'nullable|string|max:100',
            'description_column' => 'nullable|string|max:100',
            'payment_column' => 'nullable|string|max:100',
            'date_format' => 'nullable|string|max:30',
        ]);

        $content = file($validated['file']->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$content || count($content) < 2) {
            return response()->json(['message' => 'El CSV no contiene datos suficientes'], 422);
        }

        $headers = str_getcsv(array_shift($content));
        $dateColumn = $validated['date_column'] ?? 'fecha';
        $amountColumn = $validated['amount_column'] ?? 'importe';
        $descriptionColumn = $validated['description_column'] ?? 'descripcion';
        $paymentColumn = $validated['payment_column'] ?? 'metodo_pago';
        $dateFormat = $validated['date_format'] ?? 'Y-m-d';

        $matched = 0;
        $notMatched = 0;
        $rows = [];

        foreach ($content as $line) {
            $rowValues = str_getcsv($line);
            $row = array_combine($headers, $rowValues);
            if (!$row) {
                continue;
            }

            $rows[] = $row;

            try {
                $date = Carbon::createFromFormat($dateFormat, (string) ($row[$dateColumn] ?? ''))->toDateString();
            } catch (\Throwable) {
                $notMatched++;
                continue;
            }

            $amount = (float) str_replace(',', '.', (string) ($row[$amountColumn] ?? 0));
            $description = (string) ($row[$descriptionColumn] ?? '');
            $payment = (string) ($row[$paymentColumn] ?? 'Otros');

            $ticket = $request->user()->tickets()
                ->whereDate('fecha', $date)
                ->where('importe', $amount)
                ->where('conciliado', 'pendiente')
                ->where(function ($builder) use ($description) {
                    $builder->where('gasto', 'like', "%{$description}%")
                        ->orWhere('cif', 'like', "%{$description}%")
                        ->orWhereRaw('1 = 1');
                })
                ->orderBy('id')
                ->first();

            if (!$ticket) {
                $ticket = $request->user()->tickets()
                    ->whereDate('fecha', $date)
                    ->where('importe', $amount)
                    ->where('conciliado', 'pendiente')
                    ->orderBy('id')
                    ->first();
            }

            if ($ticket) {
                $ticket->update([
                    'conciliado' => 'terminado',
                    'conciliado_en' => Carbon::now(),
                    'metodo_pago' => in_array($payment, ['Efectivo', 'Tarjeta', 'Otros'], true) ? $payment : $ticket->metodo_pago,
                    'referencia_bancaria' => $description ?: $ticket->referencia_bancaria,
                ]);
                $matched++;
            } else {
                $notMatched++;
            }
        }

        return response()->json([
            'message' => 'Importación completada',
            'rows_processed' => count($rows),
            'matched' => $matched,
            'not_matched' => $notMatched,
        ], 200);
    }
}
