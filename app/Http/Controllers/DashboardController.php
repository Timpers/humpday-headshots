<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if (!$user) {
            abort(401);
        }
        
        $gamertags = $user->gamertags()->with('user')->get();
        $platformStats = $user->gamertags()
            ->selectRaw('platform, count(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform');

        return view('dashboard', compact('user', 'gamertags', 'platformStats'));
    }
}
