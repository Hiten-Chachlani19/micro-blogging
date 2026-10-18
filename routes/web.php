<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/force-migrate-db-setup', function () {
    Artisan::call('migrate:fresh', ['--force' => true]);
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return response()->json([
        'status' => 'Database Migrated Successfully!',
        'output' => Artisan::output()
    ]);
});
Route::get('/', function () {
    return view('welcome');
});
