<?php

namespace Statamic\Providers;

use Statamic\API\Site;
use Statamic\View\Store;
use Illuminate\View\View;
use Statamic\View\Cascade;
use Statamic\View\Antlers\Engine;
use Statamic\View\Antlers\Parser;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Store::class);

        $this->app->singleton(Cascade::class, function ($app) {
            return new Cascade($app['request'], Site::current());
        });

        $this->app->bind(Parser::class, function ($app) {
            return (new Parser)->callback([Engine::class, 'renderTag']);
        });

        $this->app->singleton(Engine::class, function ($app) {
            return new Engine($app['files'], $app[Parser::class]);
        });
    }

    public function boot()
    {
        View::macro('withoutExtractions', function () {
            $this->engine->withoutExtractions();
            return $this;
        });

        tap($this->app['view'], function ($view) {
            $resolver = function () {
                return $this->app[Engine::class];
            };
            $view->addExtension('antlers.html', 'antlers', $resolver);
            $view->addExtension('antlers.php', 'antlers', $resolver);
        });
    }
}
