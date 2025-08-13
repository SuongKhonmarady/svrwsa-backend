<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogAdminLogin;
use App\Listeners\LogAdminLogout;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class  => [LogAdminLogin::class],
        Logout::class => [LogAdminLogout::class],
    ];

    public function boot(): void
    {
        // Model observers are now handled in AppServiceProvider
    }
}
