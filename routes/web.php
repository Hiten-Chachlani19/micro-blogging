<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home Route
Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Backend API is running smoothly!'
    ]);
});

// Database Setup Route (Runs Migration & Clears Cache)
Route::get('/force-migrate-db-setup', function () {
    try {
        Artisan::call('migrate:fresh', ['--force' => true]);
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        
        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Database Migrated Successfully!',
            'output' => Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Database Debug Route (Checks Tables and User Count)
Route::get('/debug-db', function () {
    try {
        $tables = Schema::getTableListing();
        $userCount = DB::table('users')->count();
        
        return response()->json([
            'status' => 'CONNECTED',
            'tables' => $tables,
            'users_count' => $userCount
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ], 500);
    }
});
