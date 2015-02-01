<?php

namespace Emsifa\RouteGenerator;

use Illuminate\Filesystem\Filesystem;

class RouteActionGenerator {

	protected $route;

	protected $files;

	protected $has_generate_controller = false;
	protected $has_generate_method = false;

	public function __construct(RouteGenerator $route, $controller_file = null)
	{
		if( ! $route->isUsingController()) {
			throw new \Exception("Route must use Controller as action");
		}

		list($controller, $method) = explode('@', $route->getActionName());

		$this->route = $route;
		$this->files = new Filesystem;
		$this->controller_class = $controller;
		$this->controller_method = $method;
		$this->controller_file = $controller_file ?: app_path('controllers/'.str_replace("\\","/", $controller)).'.php'; 
	}

	public function getControllerClass()
	{
		return $this->controller_class;
	}

	public function getControllerMethod()
	{
		return $this->controller_method;
	}

	public function getControllerFile()
	{
		return $this->controller_file;
	}

	public function hasGenerateController()
	{
		return $this->has_generate_controller;
	}

	public function hasGenerateMethod()
	{
		return $this->has_generate_method;
	}

	public function generate()
	{
		if($this->route->needGenerateController()) {
			$this->generateController();
		}

		if($this->route->needGenerateMethod()) {
			$this->generateMethod();
		}
	}

	public function generateController(array $used_classes = array())
	{
		$common_facades = ['URL','View','Input','Config','Session','Response','Redirect'];
		$destination = $this->controller_file;
		$directory = dirname($destination);
		$classname = $this->getControllerClass();

		$explode_class = explode("\\", $classname);
		$has_acme_class = (1 == count($explode_class));
		$classname = array_pop($explode_class);
		$namespace = implode("\\", $explode_class);

		if( ! $this->files->exists($directory)) {
			$this->files->makeDirectory($directory, 0777, true);
		}

		if( $this->files->exists($destination)) {
			return new ControllerExistsException("Cannot generate controller file, {destination} already exists.");
		}

		$stub_file = __DIR__.'/stubs/Controller.stub';
		$content = file_get_contents($stub_file);
		
		$vars = [
			'namespace' => empty($namespace)? '' : "namespace {$namespace};\n",
			'classname' => $classname,
			'used_classes' => ''
		];

		if( ! $has_acme_class) {
			$model_classes = array();
			foreach(explode("\\", $this->getControllerClass()) as $class) {
				$class = preg_replace("/controller(s)?$/i","",$class);
				if(class_exists($class)) {
					$model_classes[] = $class;
				}
			}

			$used_classes = array_merge([
				'BaseController',
				'facades' => $common_facades,
				'models' => $model_classes
			], $used_classes);
		}

		// make used classes
		foreach ($used_classes as $key => $value) {
			if(is_array($value) AND !empty($value)) {
				$value = array_unique($value);
				$vars['used_classes'] .= "\n\n//# Used {$key}\n";
				foreach($value as $class) {
					$vars['used_classes'] .= "use {$class};\n";
				}
			} elseif(is_string($value)) {
				$vars['used_classes'] .= "\nuse {$value};";
			}
		}

		$vars['used_classes'] = "\n".trim(preg_replace("/\n\n\n/", "\n\n", $vars['used_classes']));

		foreach($vars as $var => $value) {
			$content = preg_replace('/{[ ]*'.$var.'[ ]*}/', $value, $content);
		}

		$make = $this->files->put($destination, $content);

		$this->has_generate_controller = true;

		return $make;
	}

	public function generateMethod(array $doc_lines = array())
	{
		$controller_file = $this->getControllerFile();
		$controller_content = trim(file_get_contents($controller_file));
		$method = $this->getControllerMethod();

		if(preg_match("/((static|final)? *)?(public|protected|private) ((static|final)? *)?function {$method}[^a-zA-Z0-9_]/", $controller_content)) {
			return false;
		}

		$route_params = $this->route->parseParams();

		$params_arr = [];
		foreach($route_params as $param => $value) {
			$params_arr[] = is_null($value)? "\${$param}" : "\${$param} = {$value}";
		}

		$params_str = implode(", ", $params_arr);
		$exception = "throw new \Exception('Edit me at \"".str_replace(base_path().'/', "", $controller_file)."\" dude!');";

		$doc_notations = array();

		$route_data = [
			'name' => $this->route->getName(),
			'route' => $this->route->resolvedUri(),
			'before' => $this->route->getFiltersBefore(),
			'after' => $this->route->getFiltersAfter()
		];

		$route_str = $this->route->getMethodsString().' '.$this->route->resolvedUri();

		$route_data['route'] = $route_str;

		foreach($route_data as $key => $value) {
			if(is_string($value) AND !empty($value)) {
				$doc_lines[] = "@{$key}\t{$value}";
			}
		}

		$route_params = $this->route->parseParams();
		$route_conditions = $this->route->getConditions();

		if(!empty($route_params)) {
			$doc_lines[] = '-------------------------------';
		}

		foreach ($route_params as $param => $default_value) {
			$type = "string";

			if( ! is_null($default_value) 
				AND 
				(
					in_array($default_value, ['false','true','null','array()']) 
					OR 
					is_numeric($default_value)
				)
			) {
				$type = "mixed";
			}

			$param_anotation = "@param\t{$type} \${$param}";
			if(array_key_exists($param, $route_conditions)) {
				$param_anotation .= " ".$route_conditions[$param];
			}

			$doc_lines[] = $param_anotation;
		}

		array_unshift($doc_lines, '');

		$doc_str = implode("\n\r\t * ", $doc_lines);
		$doc_notation_str = "/**{$doc_str}\n\r\t */";

		$method_code = "\n\n\r\t{$doc_notation_str}\n\r\tpublic function {$method}({$params_str})\n\r\t{\n\r\t\t{$exception}\n\r\t}\n\r\n"; 
		$replacer = $method_code."}";

		$content = preg_replace("/([\n\t\r]+\})$/", $replacer, $controller_content);

		$make = $this->files->put($controller_file, $content);

		$this->has_generate_method = true;

		return $make;
	}

}