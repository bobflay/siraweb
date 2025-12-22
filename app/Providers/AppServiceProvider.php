<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Notification;
use App\Models\Client;
use App\Models\VisitPhoto;
use App\Observers\NotificationObserver;
use App\Observers\ClientObserver;
use App\Observers\VisitPhotoObserver;
use App\Policies\ClientPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Client::class => ClientPolicy::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register observers
        Notification::observe(NotificationObserver::class);
        Client::observe(ClientObserver::class);
        VisitPhoto::observe(VisitPhotoObserver::class);

        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
