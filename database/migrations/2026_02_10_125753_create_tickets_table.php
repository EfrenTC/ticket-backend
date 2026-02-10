<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('gasto');
            $table->decimal('importe', 10, 2);
            $table->enum('categoria', [
                'Restauración',
                'Aparcamiento',
                'Peaje',
                'Transporte',
                'Alojamiento',
                'Gasolina',
                'Otros'
            ]);
            $table->string('cif', 20);
            $table->enum('metodo_pago', ['Efectivo', 'Tarjeta', 'Otros']);
            $table->enum('conciliado', ['pendiente', 'terminado'])->default('pendiente');
            $table->date('fecha');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
