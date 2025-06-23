<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GamertagController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/tailwind-test', function () {
    return view('tailwind-test');
})->name('tailwind.test');

Route::get('/gamertag-test', function () {
    $users = \App\Models\User::with('gamertags')->get();
    $platformStats = \App\Models\Gamertag::selectRaw('platform, count(*) as count')
        ->groupBy('platform')
        ->pluck('count', 'platform');
    
    $totalUsers = \App\Models\User::count();
    $totalGamertags = \App\Models\Gamertag::count();
    $publicGamertags = \App\Models\Gamertag::where('is_public', true)->count();
    $primaryGamertags = \App\Models\Gamertag::where('is_primary', true)->count();
    
    return view('gamertag-test', compact(
        'users', 
        'platformStats', 
        'totalUsers', 
        'totalGamertags', 
        'publicGamertags', 
        'primaryGamertags'
    ));
})->name('gamertag.test');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});

// Gamertag Routes (protected)
Route::middleware('auth')->group(function () {
    Route::resource('gamertags', GamertagController::class);
    
    // Additional gamertag routes
    Route::get('gamertags/user/{user}', [GamertagController::class, 'userGamertags'])->name('gamertags.user');
    Route::get('gamertags/platform/{platform}', [GamertagController::class, 'platform'])->name('gamertags.platform');
});

// Game Routes (protected)
Route::middleware('auth')->group(function () {
    Route::resource('games', \App\Http\Controllers\GameController::class);
    Route::post('games/search', [\App\Http\Controllers\GameController::class, 'search'])->name('games.search');
});
