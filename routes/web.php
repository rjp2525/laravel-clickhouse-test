<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-clickhouse', function () {
    try {
        $version = DB::connection('clickhouse')->select('SELECT version()');

        return response()->json(['success' => true, 'version' => $version]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
});
