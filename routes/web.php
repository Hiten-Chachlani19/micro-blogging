<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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



// DIRECT TEST LOGIN ROUTE - Catch exact token error
Route::get('/test-login-debug', function (Request $request) {
    try {
        $email = $request->query('email');
        $password = $request->query('password');

        if (!$email || !$password) {
            return response()->json(['error' => 'Please pass ?email=your_email&password=your_password in URL']);
        }

        // 1. Check if user exists
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['status' => 'USER_NOT_FOUND', 'message' => 'No user exists with this email.']);
        }

        // 2. Attempt Auth
        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            return response()->json(['status' => 'INVALID_CREDENTIALS', 'message' => 'Password does not match.']);
        }

        // 3. Attempt Sanctum Token Creation
        if (!method_exists($user, 'createToken')) {
            return response()->json([
                'status' => 'MISSING_SANCTUM_TRAIT',
                'message' => 'User model is missing HasApiTokens trait! Add "use Laravel\Sanctum\HasApiTokens;" inside User.php'
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

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

config(['cache.default' => 'file']);
config(['session.driver' => 'file']);
config(['queue.default' => 'sync']);

Route::get('/', function () {
    return response()->json(['status' => 'API Active']);
});

// Force Clear All Config & Cache
Route::get('/clear-redis-config', function () {
    try {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        
        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Redis configurations bypassed and all caches cleared!'
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ], 500);
    }
});
