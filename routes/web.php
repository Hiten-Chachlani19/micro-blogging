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

Route::get('/test-login-debug', function (Request $request) {
    try {
        $email = $request->query('email');
        $password = $request->query('password');

        if (!$email || !$password) {
            return response()->json(['error' => 'Please pass ?email=your_email&password=your_password in URL']);
        }

        // Attempt Auth
        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            return response()->json(['status' => 'INVALID_CREDENTIALS', 'message' => 'Email or Password incorrect.']);
        }

        $user = Auth::user();
        
        // Attempt Token Creation (Sanctum/Passport)
        $token = method_exists($user, 'createToken') ? $user->createToken('auth_token')->plainTextToken : 'Auth success (No createToken method found on User model)';

        return response()->json([
            'status' => 'SUCCESS',
            'user' => $user,
            'token' => $token
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'CRASH_EXCEPTION',
            'error_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});
