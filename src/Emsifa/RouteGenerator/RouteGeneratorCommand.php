<?php

namespace Emsifa\RouteGenerator;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Route;
use File;

class RouteGeneratorCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'route:generate';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Route generator';

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
		$uri = $this->argument("uri");
		$method = $this->argument("method");
		$route_file = base_path($this->option("file"));
		$conditions = $this->option("where");
		$action = [
			'uses' => $this->argument("uses"),
			'after' => $this->option("after"),
			'before' => $this->option("before"),
			'as' => $this->option("name")
		];

		foreach($action as $key => $val) {
			if(is_null($val)) unset($action[$key]);
		}

		$route_generator = new RouteGenerator($method, $uri, $action);
		$route_str = strtoupper($method)." ".$route_generator->resolvedUri();
		$route_generator->generate($route_file, true);	
		$this->info("# Generate route '{$route_str}' using '".$route_generator->getActionName()."'");

		$action_generator = $route_generator->getActionGenerator();
		$controller_class = $action_generator->getControllerClass();
		$controller_method = $action_generator->getControllerMethod();

		if($action_generator->hasGenerateController()) {
			echo "> Generate controller '{$controller_class}'\n";
		}

		if($action_generator->hasGenerateMethod()) {
			echo "> Generate method '{$controller_method}' in controller '{$controller_class}'\n";
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('method', InputArgument::REQUIRED, 'Route method'),
			array('uri', InputArgument::REQUIRED, 'Route path'),
			array('uses', InputArgument::REQUIRED, 'Controller method'),
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
			array('file', 'f', InputOption::VALUE_OPTIONAL, 'Route file', 'app/routes.php'),
			array('name', 'N', InputOption::VALUE_OPTIONAL, 'Route name', null),
			array('before', 'b', InputOption::VALUE_OPTIONAL, 'Before filters', null),
			array('after', 'a', InputOption::VALUE_OPTIONAL, 'After filters', null),
			array('where', 'w', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Param Conditions', null),
		);
	}

}
