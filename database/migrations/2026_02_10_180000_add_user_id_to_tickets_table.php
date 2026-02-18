<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tickets') && !Schema::hasColumn('tickets', 'user_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tickets') && Schema::hasColumn('tickets', 'user_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
