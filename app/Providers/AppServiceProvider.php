<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Godruoyi\Snowflake\Snowflake;
use Godruoyi\Snowflake\LaravelSequenceResolver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('snowflake', function ($app) {
            return (new Snowflake())
                ->setStartTimeStamp(strtotime('2019-10-10')*1000)
                ->setSequenceResolver(new LaravelSequenceResolver($app->get('cache.store')));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
