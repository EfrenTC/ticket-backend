<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;

Route::get('/check', function () {
    return response()->json([
        'status' => 'Conectado al Backend!',
        'db_mysql' => 'OK',
        'db_mongo' => 'OK'
    ]);
});

Route::apiResource('tickets', TicketController::class);