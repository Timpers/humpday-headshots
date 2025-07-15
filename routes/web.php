<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GamertagController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameCompatibilityController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GamingSessionController;
use App\Http\Controllers\GamingSessionMessageController;
use App\Http\Controllers\UserConnectionController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupInvitationController;
use App\Http\Controllers\NotificationController;

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
        // Game Compatibility
    Route::get('/games/compatibility', [GameCompatibilityController::class, 'index'])->name('games.compatibility.index');
    Route::get('/games/compatibility/compare/{user}', [GameCompatibilityController::class, 'compare'])->name('games.compatibility.compare');
    Route::get('/games/compatibility/api/{user}', [GameCompatibilityController::class, 'getCompatibility'])->name('games.compatibility.api');

    Route::resource('games', GameController::class);
    Route::post('games/search', [GameController::class, 'search'])->name('games.search');
});

// Social/Connection Routes (protected)
Route::middleware('auth')->group(function () {
    // Social hub pages
    Route::get('/social', [SocialController::class, 'index'])->name('social.index');
    Route::get('/social/search', [SocialController::class, 'search'])->name('social.search');
    Route::post('/social/search', [SocialController::class, 'search']);
    Route::get('/social/browse', [SocialController::class, 'browse'])->name('social.browse');
    Route::get('/social/friends', [SocialController::class, 'friends'])->name('social.friends');
    Route::get('/social/requests', [SocialController::class, 'requests'])->name('social.requests');

    // Connection management
    Route::get('/user-connections/create', [UserConnectionController::class, 'create'])->name('user-connections.create');
    Route::post('/user-connections', [UserConnectionController::class, 'store'])->name('user-connections.store');
    Route::post('/connections', [UserConnectionController::class, 'store'])->name('connections.store'); // Alias for convenience
    Route::post('/user-connections/{connection}/accept', [UserConnectionController::class, 'accept'])->name('user-connections.accept');
    Route::post('/connections/{connection}/accept', [UserConnectionController::class, 'accept'])->name('connections.accept'); // Alias
    Route::post('/user-connections/{connection}/decline', [UserConnectionController::class, 'decline'])->name('user-connections.decline');
    Route::post('/connections/{connection}/decline', [UserConnectionController::class, 'decline'])->name('connections.decline'); // Alias
    Route::post('/user-connections/{connection}/cancel', [UserConnectionController::class, 'cancel'])->name('user-connections.cancel');
    Route::post('/connections/{connection}/cancel', [UserConnectionController::class, 'cancel'])->name('connections.cancel'); // Alias
    Route::post('/user-connections/{connection}/block', [UserConnectionController::class, 'block'])->name('user-connections.block');
    Route::post('/connections/{connection}/block', [UserConnectionController::class, 'block'])->name('connections.block'); // Alias
    Route::delete('/user-connections/{connection}', [UserConnectionController::class, 'destroy'])->name('user-connections.destroy');
    Route::delete('/connections/{connection}', [UserConnectionController::class, 'destroy'])->name('connections.destroy'); // Alias
});

// Public Group Routes (no authentication required)
Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');

// Group Routes (protected)
Route::middleware('auth')->group(function () {
    // Group management (protected routes) - specific routes first
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::get('/my-groups', [GroupController::class, 'myGroups'])->name('groups.my-groups');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}/edit', [GroupController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('/groups/{group}/join', [GroupController::class, 'join'])->name('groups.join');
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');

    // Group invitations
    Route::get('/group-invitations', [GroupInvitationController::class, 'index'])->name('group-invitations.index');
    Route::get('/my-invitations', [GroupInvitationController::class, 'index'])->name('groups.my-invitations');
    Route::post('/group-invitations', [GroupInvitationController::class, 'store'])->name('group-invitations.store');
    Route::get('/group-invitations/{invitation}', [GroupInvitationController::class, 'show'])->name('group-invitations.show');
    Route::post('/group-invitations/{invitation}/accept', [GroupInvitationController::class, 'accept'])->name('group-invitations.accept');
    Route::post('/group-invitations/{invitation}/decline', [GroupInvitationController::class, 'decline'])->name('group-invitations.decline');
    Route::post('/group-invitations/{invitation}/cancel', [GroupInvitationController::class, 'cancel'])->name('group-invitations.cancel');
    Route::post('/group-invitations/bulk-action', [GroupInvitationController::class, 'bulkAction'])->name('group-invitations.bulk-action');

    // Gaming Sessions
    Route::resource('gaming-sessions', GamingSessionController::class);
    Route::post('/gaming-sessions/{gamingSession}/join', [GamingSessionController::class, 'join'])->name('gaming-sessions.join');
    Route::post('/gaming-sessions/{gamingSession}/leave', [GamingSessionController::class, 'leave'])->name('gaming-sessions.leave');
    Route::get('/search-games', [GamingSessionController::class, 'searchGames'])->name('gaming-sessions.search-games');
    Route::post('/gaming-sessions/invitations/{invitation}/respond', [GamingSessionController::class, 'respondToInvitation'])->name('gaming-sessions.respond-invitation');
    
    // Gaming Session Messages
    Route::get('/gaming-sessions/{session}/messages', [GamingSessionMessageController::class, 'index'])->name('gaming-sessions.messages.index');
    Route::post('/gaming-sessions/{session}/messages', [GamingSessionMessageController::class, 'store'])->name('gaming-sessions.messages.store');
    Route::put('/gaming-sessions/{session}/messages/{message}', [GamingSessionMessageController::class, 'update'])->name('gaming-sessions.messages.update');
    Route::delete('/gaming-sessions/{session}/messages/{message}', [GamingSessionMessageController::class, 'destroy'])->name('gaming-sessions.messages.destroy');
    Route::get('/gaming-sessions/{session}/messages/recent', [GamingSessionMessageController::class, 'recent'])->name('gaming-sessions.messages.recent');
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('/notifications/count', [NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::post('/notifications/subscribe', [NotificationController::class, 'subscribe'])->name('notifications.subscribe');
    Route::post('/notifications/test', [NotificationController::class, 'test'])->name('notifications.test');

});

// Groups show route (public but needs to be after specific auth routes)
Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');

Route::middleware('auth')->group(function () {
});
