<?php

namespace App\Providers;

use App\Models\GamingSession;
use App\Models\GamingSessionMessage;
use App\Policies\GamingSessionPolicy;
use App\Policies\GamingSessionMessagePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        GamingSession::class => GamingSessionPolicy::class,
        GamingSessionMessage::class => GamingSessionMessagePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
