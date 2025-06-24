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

// Social/Connection Routes (protected)
Route::middleware('auth')->group(function () {
    // Social hub pages
    Route::get('/social', [\App\Http\Controllers\SocialController::class, 'index'])->name('social.index');
    Route::get('/social/search', [\App\Http\Controllers\SocialController::class, 'search'])->name('social.search');
    Route::post('/social/search', [\App\Http\Controllers\SocialController::class, 'search']);
    Route::get('/social/browse', [\App\Http\Controllers\SocialController::class, 'browse'])->name('social.browse');
    Route::get('/social/friends', [\App\Http\Controllers\SocialController::class, 'friends'])->name('social.friends');
    Route::get('/social/requests', [\App\Http\Controllers\SocialController::class, 'requests'])->name('social.requests');
    
    // Connection management
    Route::post('/connections', [\App\Http\Controllers\UserConnectionController::class, 'store'])->name('connections.store');
    Route::patch('/connections/{connection}/accept', [\App\Http\Controllers\UserConnectionController::class, 'accept'])->name('connections.accept');
    Route::patch('/connections/{connection}/decline', [\App\Http\Controllers\UserConnectionController::class, 'decline'])->name('connections.decline');
    Route::delete('/connections/{connection}/cancel', [\App\Http\Controllers\UserConnectionController::class, 'cancel'])->name('connections.cancel');
    Route::patch('/connections/{connection}/block', [\App\Http\Controllers\UserConnectionController::class, 'block'])->name('connections.block');
    Route::delete('/connections/{connection}', [\App\Http\Controllers\UserConnectionController::class, 'destroy'])->name('connections.destroy');
});

// Group Routes (protected)
Route::middleware('auth')->group(function () {
    // Group management
    Route::resource('groups', \App\Http\Controllers\GroupController::class);
    Route::get('/my-groups', [\App\Http\Controllers\GroupController::class, 'myGroups'])->name('groups.my-groups');
    Route::post('/groups/{group}/join', [\App\Http\Controllers\GroupController::class, 'join'])->name('groups.join');
    Route::delete('/groups/{group}/leave', [\App\Http\Controllers\GroupController::class, 'leave'])->name('groups.leave');
    
    // Group invitations
    Route::get('/my-invitations', [\App\Http\Controllers\GroupInvitationController::class, 'index'])->name('groups.my-invitations');
    Route::post('/group-invitations', [\App\Http\Controllers\GroupInvitationController::class, 'store'])->name('group-invitations.store');
    Route::get('/group-invitations/{invitation}', [\App\Http\Controllers\GroupInvitationController::class, 'show'])->name('group-invitations.show');
    Route::patch('/group-invitations/{invitation}/accept', [\App\Http\Controllers\GroupInvitationController::class, 'accept'])->name('group-invitations.accept');
    Route::patch('/group-invitations/{invitation}/decline', [\App\Http\Controllers\GroupInvitationController::class, 'decline'])->name('group-invitations.decline');
    Route::delete('/group-invitations/{invitation}/cancel', [\App\Http\Controllers\GroupInvitationController::class, 'cancel'])->name('group-invitations.cancel');
    Route::post('/group-invitations/bulk-action', [\App\Http\Controllers\GroupInvitationController::class, 'bulkAction'])->name('group-invitations.bulk-action');
});
