<?php

namespace Emsifa\RouteGenerator;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Route;

class RouteActionsGeneratorCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'route:generate-actions';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Route actions generator';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$routes = Route::getRoutes();

		$added_controllers = array();
		$added_methods = array();

		foreach($routes as $route) {
			$route_generator = RouteGenerator::makeFromRoute($route);

			if($route_generator->isUsingController()) {
				$route_generator->generateRouteAction();
				$action_generator = $route_generator->getActionGenerator();
				$controller = $action_generator->getControllerClass();
				$method = $action_generator->getControllerMethod();

				if($action_generator->hasGenerateController()) {
					$added_controllers[] = $controller;
					$this->info("# Generate Controller '{$controller}'");
				}

				if($action_generator->hasGenerateMethod()) {
					$added_methods[] = $controller.':'.$method;
					echo "> Generate method '{$method}' in controller '{$controller}'\n";
				}
			}
		}

		$count_added_controllers = count($added_controllers);
		$count_added_methods = count($added_methods);
		
		if(($count_added_methods + $count_added_controllers) > 0) {
			echo "\n";
		}

		$this->info("# DONE!! {$count_added_controllers} Controllers added, {$count_added_methods} Methods added");
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
		);
	}

}
