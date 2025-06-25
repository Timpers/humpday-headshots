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

// Public Gamertag Routes
Route::get('gamertags', [GamertagController::class, 'index'])->name('gamertags.index');
Route::get('gamertags/user/{user}', [GamertagController::class, 'userGamertags'])->name('gamertags.user');
Route::get('gamertags/platform/{platform}', [GamertagController::class, 'platform'])->name('gamertags.platform');

// Protected Gamertag Routes
Route::middleware('auth')->group(function () {
    Route::get('gamertags/create', [GamertagController::class, 'create'])->name('gamertags.create');
    Route::post('gamertags', [GamertagController::class, 'store'])->name('gamertags.store');
    Route::get('gamertags/{gamertag}', [GamertagController::class, 'show'])->name('gamertags.show');
    Route::get('gamertags/{gamertag}/edit', [GamertagController::class, 'edit'])->name('gamertags.edit');
    Route::put('gamertags/{gamertag}', [GamertagController::class, 'update'])->name('gamertags.update');
    Route::delete('gamertags/{gamertag}', [GamertagController::class, 'destroy'])->name('gamertags.destroy');
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
    Route::get('/user-connections/create', [\App\Http\Controllers\UserConnectionController::class, 'create'])->name('user-connections.create');
    Route::post('/user-connections', [\App\Http\Controllers\UserConnectionController::class, 'store'])->name('user-connections.store');
    Route::post('/connections', [\App\Http\Controllers\UserConnectionController::class, 'store'])->name('connections.store'); // Alias for convenience
    Route::post('/user-connections/{connection}/accept', [\App\Http\Controllers\UserConnectionController::class, 'accept'])->name('user-connections.accept');
    Route::post('/user-connections/{connection}/decline', [\App\Http\Controllers\UserConnectionController::class, 'decline'])->name('user-connections.decline');
    Route::post('/user-connections/{connection}/cancel', [\App\Http\Controllers\UserConnectionController::class, 'cancel'])->name('user-connections.cancel');
    Route::post('/user-connections/{connection}/block', [\App\Http\Controllers\UserConnectionController::class, 'block'])->name('user-connections.block');
    Route::delete('/user-connections/{connection}', [\App\Http\Controllers\UserConnectionController::class, 'destroy'])->name('user-connections.destroy');
});

// Group Routes (protected)
Route::middleware('auth')->group(function () {
    // Group management
    Route::resource('groups', \App\Http\Controllers\GroupController::class);
    Route::get('/my-groups', [\App\Http\Controllers\GroupController::class, 'myGroups'])->name('groups.my-groups');
    Route::post('/groups/{group}/join', [\App\Http\Controllers\GroupController::class, 'join'])->name('groups.join');
    Route::post('/groups/{group}/leave', [\App\Http\Controllers\GroupController::class, 'leave'])->name('groups.leave');

    // Group invitations
    Route::get('/group-invitations', [\App\Http\Controllers\GroupInvitationController::class, 'index'])->name('group-invitations.index');
    Route::get('/my-invitations', [\App\Http\Controllers\GroupInvitationController::class, 'index'])->name('groups.my-invitations');
    Route::post('/group-invitations', [\App\Http\Controllers\GroupInvitationController::class, 'store'])->name('group-invitations.store');
    Route::get('/group-invitations/{invitation}', [\App\Http\Controllers\GroupInvitationController::class, 'show'])->name('group-invitations.show');
    Route::post('/group-invitations/{invitation}/accept', [\App\Http\Controllers\GroupInvitationController::class, 'accept'])->name('group-invitations.accept');
    Route::post('/group-invitations/{invitation}/decline', [\App\Http\Controllers\GroupInvitationController::class, 'decline'])->name('group-invitations.decline');
    Route::post('/group-invitations/{invitation}/cancel', [\App\Http\Controllers\GroupInvitationController::class, 'cancel'])->name('group-invitations.cancel');
    Route::post('/group-invitations/bulk-action', [\App\Http\Controllers\GroupInvitationController::class, 'bulkAction'])->name('group-invitations.bulk-action');

    // Gaming Sessions
    Route::resource('gaming-sessions', \App\Http\Controllers\GamingSessionController::class);
    Route::post('/gaming-sessions/{gamingSession}/join', [\App\Http\Controllers\GamingSessionController::class, 'join'])->name('gaming-sessions.join');
    Route::post('/gaming-sessions/{gamingSession}/leave', [\App\Http\Controllers\GamingSessionController::class, 'leave'])->name('gaming-sessions.leave');
    Route::get('/search-games', [\App\Http\Controllers\GamingSessionController::class, 'searchGames'])->name('gaming-sessions.search-games');
    Route::post('/gaming-sessions/invitations/{invitation}/respond', [\App\Http\Controllers\GamingSessionController::class, 'respondToInvitation'])->name('gaming-sessions.respond-invitation');
    
    // Gaming Session Messages
    Route::get('/gaming-sessions/{session}/messages', [\App\Http\Controllers\GamingSessionMessageController::class, 'index'])->name('gaming-sessions.messages.index');
    Route::post('/gaming-sessions/{session}/messages', [\App\Http\Controllers\GamingSessionMessageController::class, 'store'])->name('gaming-sessions.messages.store');
    Route::put('/gaming-sessions/{session}/messages/{message}', [\App\Http\Controllers\GamingSessionMessageController::class, 'update'])->name('gaming-sessions.messages.update');
    Route::delete('/gaming-sessions/{session}/messages/{message}', [\App\Http\Controllers\GamingSessionMessageController::class, 'destroy'])->name('gaming-sessions.messages.destroy');
    Route::get('/gaming-sessions/{session}/messages/recent', [\App\Http\Controllers\GamingSessionMessageController::class, 'recent'])->name('gaming-sessions.messages.recent');
    
    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/notifications/count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::post('/notifications/subscribe', [\App\Http\Controllers\NotificationController::class, 'subscribe'])->name('notifications.subscribe');
    Route::post('/notifications/test', [\App\Http\Controllers\NotificationController::class, 'test'])->name('notifications.test');

    // Game Compatibility
    Route::get('/games/compatibility', [\App\Http\Controllers\GameCompatibilityController::class, 'index'])->name('games.compatibility.index');
    Route::get('/games/compatibility/compare/{user}', [\App\Http\Controllers\GameCompatibilityController::class, 'compare'])->name('games.compatibility.compare');
    Route::get('/games/compatibility/api/{user}', [\App\Http\Controllers\GameCompatibilityController::class, 'getCompatibility'])->name('games.compatibility.api');
});
