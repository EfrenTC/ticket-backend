<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('categoria')->constrained()->nullOnDelete();
            $table->timestamp('conciliado_en')->nullable()->after('conciliado');
            $table->string('referencia_bancaria')->nullable()->after('conciliado_en');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['conciliado_en', 'referencia_bancaria']);
        });
    }
};
