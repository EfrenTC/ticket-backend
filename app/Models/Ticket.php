<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'gasto',
        'importe',
        'categoria',
        'cif',
        'metodo_pago',
        'conciliado',
        'fecha',
    ];
    
    protected $casts = [
        'importe' => 'decimal:2',
        'fecha' => 'date',
    ];
}