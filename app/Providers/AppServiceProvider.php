<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $compiledViewPath = env('VIEW_COMPILED_PATH')
            ?: sys_get_temp_dir().DIRECTORY_SEPARATOR.'krakatau-crm'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'views';

        $this->app['config']->set('view.compiled', $compiledViewPath);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        File::ensureDirectoryExists((string) config('view.compiled'));

        Relation::morphMap([
            'lead' => \App\Models\Lead::class,
            'opportunity' => \App\Models\Opportunity::class,
            'customer' => \App\Models\Customer::class,
        ]);
    }
}
