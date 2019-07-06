<?php

namespace Sandeepchowdary7\Laraeventful;

use Illuminate\Support\ServiceProvider;
use Sandeepchowdary7\Laraeventful\Eventful;

class LaraeventfulServiceProvider extends ServiceProvider 
    {
        public function register()
        {
            $this->app->singleton('eventful', function($app) {
                return new Eventful();
            });
        }

        public function boot()
        {
            $this->publishes([
                __DIR__ . '/config/eventful.php' => config_path('eventful.php'),
            ]);
            $this->mergeConfigFrom(
                __DIR__ . '/config/eventful.php', 'eventful'
            );
        }

        public function provides()
        {
            return [
                'Eventful',
            ];
        }
}
