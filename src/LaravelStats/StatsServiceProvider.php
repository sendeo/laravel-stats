<?php

namespace Sendeo\LaravelStats;

use Illuminate\Support\ServiceProvider;

class StatsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
         if ($this->app->runningInConsole()) {
            $this->commands([
                Stats::class,
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
