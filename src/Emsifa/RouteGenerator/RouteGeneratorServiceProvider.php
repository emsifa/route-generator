<?php namespace Emsifa\RouteGenerator;

use Illuminate\Support\ServiceProvider;

class RouteGeneratorServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

        $this->app['generator.route'] = $this->app->share(function($app)
        {
            return new RouteGeneratorCommand();
        });

        $this->app['generator.route_actions'] = $this->app->share(function($app)
        {
            return new RouteActionsGeneratorCommand();
        });

        $this->commands('generator.route');
        $this->commands('generator.route_actions');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
