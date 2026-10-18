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

// Home Check Route
Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Backend API is running smoothly!'
    ]);
});

// ONE-CLICK TOTAL FIX ROUTE
Route::get('/fix-everything-now', function () {
    try {
        // 1. Publish Sanctum migrations if missing
        Artisan::call('vendor:publish', [
            '--provider' => 'Laravel\Sanctum\SanctumServiceProvider',
            '--force' => true
        ]);

        // 2. Run all pending migrations cleanly without dropping users
        Artisan::call('migrate', ['--force' => true]);

        // 3. Clear all cached configurations and storage caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');

        // 4. Return current database status
        $tables = Schema::getTableListing();
        $userCount = DB::table('users')->count();

        return response()->json([
            'status' => 'SUCCESS! All database tables & tokens configured.',
            'total_users_in_db' => $userCount,
            'existing_tables' => $tables
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Quick DB Debug Check
Route::get('/debug-db', function () {
    try {
        return response()->json([
            'status' => 'CONNECTED',
            'tables' => Schema::getTableListing(),
            'users_count' => DB::table('users')->count()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ], 500);
    }
});
