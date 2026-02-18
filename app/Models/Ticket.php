<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gasto',
        'importe',
        'categoria',
        'category_id',
        'cif',
        'metodo_pago',
        'conciliado',
        'conciliado_en',
        'referencia_bancaria',
        'fecha',
    ];

    protected $casts = [
        'importe' => 'decimal:2',
        'fecha' => 'date',
        'conciliado_en' => 'datetime',
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

        public function category()
        {
            return $this->belongsTo(Category::class);
        }

        public function tags()
        {
            return $this->belongsToMany(Tag::class)->withTimestamps();
        }
}