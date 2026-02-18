<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('currency', 10)->default('EUR')->after('avatar_url');
            $table->string('date_format', 20)->default('d/m/Y')->after('currency');
            $table->string('language', 10)->default('es')->after('date_format');
            $table->boolean('dark_mode')->default(false)->after('language');
            $table->enum('report_frequency', ['none', 'weekly', 'monthly'])->default('none')->after('dark_mode');
            $table->boolean('budget_alerts_enabled')->default(true)->after('report_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_url',
                'currency',
                'date_format',
                'language',
                'dark_mode',
                'report_frequency',
                'budget_alerts_enabled',
            ]);
        });
    }
};
