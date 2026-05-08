<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'lead' => \App\Models\Lead::class,
            'opportunity' => \App\Models\Opportunity::class,
            'customer' => \App\Models\Customer::class,
        ]);
    }
}
