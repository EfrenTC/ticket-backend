<?php

use Illuminate\Support\Facades\Route;

Route::get('/check', function () {
    return response()->json([
        'status' => 'Conectado al Backend!',
        'db_mysql' => 'OK',
        'db_mongo' => 'OK'
    ]);
});