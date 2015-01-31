<?php

namespace Emsifa\RouteGenerator;

use Illuminate\Routing\Route;
use Exception;
use Route as Router;

class RouteGenerator extends Route {

	public $available_methods = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE');

	protected $action_generator;

	public function __construct($methods, $uri, $action)
	{
		parent::__construct($methods, $uri, $action);
		$this->action_generator = new RouteActionGenerator($this);
	}

	public function getConditions()
	{
		return $this->wheres;
	}

	public function getActionName()
	{
		$action = $this->getAction();
		if(is_string($action['uses'])) {
			return $action['uses'];
		}

		return parent::getActionName();
	}

	public function getFiltersBefore()
	{
		$action = $this->getAction();
		if(!isset($action['before'])) return '';

		return is_array($action['before'])? implode('|', $action['before']) : $action['before'];
	}

	public function getFiltersAfter()
	{
		$action = $this->getAction();
		if(!isset($action['after'])) return '';

		return is_array($action['after'])? implode('|', $action['after']) : $action['after'];
	}

	public function getMethodsString()
	{
		$methods = $this->getMethods();

		foreach($methods as $i => $method) {
			$methods[$i] = strtoupper($method);
		}

		if(count($methods) == 2 AND in_array('HEAD', $methods) AND in_array('GET', $methods)) {
			return 'GET';
		}

		return implode('|', $methods);
	}

	public function isUsingController()
	{
		return ("closure" != strtolower($this->getActionName()));
	}

	public function getActionGenerator()
	{
		return $this->action_generator;
	}

	public function generate($route_file, $generate_action = true) {
		if($this->routeHasRegistered()) {
			$methods = $this->getMethodsString();
			$uri = $this->resolvedUri();
			throw new RouteHasRegisteredException("Route '{$methods} {$uri}' has registered before");
		}

		$this->generateRoute($route_file);
		
		if($generate_action) {
			$this->generateRouteAction();
		}
	}

	public function generateRouteAction($controller_file = null)
	{
		if( ! $this->needGenerateRouteAction()) {
			return false;
		}

		$this->action_generator->generate();
	}

	protected function generateRoute($route_file)
	{
		if ( ! file_exists($route_file)) {
			throw new RoutesFileNotFoundException("Cannot generate route, target route file not found");
		}

		$route_code = $this->makeRouteCode();

		file_put_contents($route_file, trim(file_get_contents($route_file))."\n\n".$route_code);
	}

	protected function makeRouteCode()
	{
		$conditions = $this->getConditions();
		$action = $this->getAction();
		$uri = $this->resolvedUri();
		$methods = $this->getMethods();
		$method = strtolower($methods[0]);

		$before_filters = array_get($action, 'before');
		$after_filters = array_get($action, 'after');

		$route_action = [
			'as' => $this->getName(),			
			'before' => is_array($before_filters)? implode('|', $before_filters) : $before_filters,
			'after' => is_array($after_filters)? implode('|', $after_filters) : $after_filters,
			'uses' => $this->getRouteAction(),
		];

		$action_arr_def = array();

		foreach ($route_action as $key => $value) {
			if(is_string($value)) {
				$action_arr_def[] = "'{$key}' => '{$value}'";
			}
		}

		$only_uses = true;
		foreach ($route_action as $key => $value) {
			if($key != "uses" AND !empty($value)) {
				$only_uses = false;
			}
		}

		$actions_str = implode(",\n\r\t", $action_arr_def);

		$route_data = $only_uses? "'".$route_action['uses']."'" : "[\n\r\t{$actions_str}\n\r\t]";
		$code = "Route::{$method}('{$uri}', ".$route_data.")";

		foreach ($conditions as $param => $condition) {
			$code .= "\n\r\t->where('{$param}', '{$condition}')";
		}

		$code .= ";";
		return $code;
	}

	public function needGenerateRouteAction()
	{
		return (
			$this->isUsingController() 
			AND (
				$this->needGenerateController() 
				OR 
				$this->needGenerateMethod()
			)
		);
	}

	public function needGenerateController()
	{
		list($controller, $method) = explode('@', $this->getActionName(), 2);
		return (false == class_exists($controller));
	}

	public function needGenerateMethod()
	{
		list($controller, $method) = explode('@', $this->getActionName(), 2);
		return (false == method_exists($controller, $method));
	}

	protected function getRouteAction()
	{
		$route_action = $this->getActionName();

		if( ! $this->isUsingController()) {
			$params_code = $this->makeParamsCode();
			$route_action = "function({$params_code}){\n\r\t\t\t\n\r\t}";
		}

		return $route_action;
	}

	public function parseParams()
	{
		$uri = $this->getUri();
		preg_match_all("/{(?<params>\w+)(\?(=(?<default>\S+))?)?}/", $uri, $match);

		$route_params_key = $match['params'];
		$route_params_value = $match['default'];

		$route_params = array();
		foreach($route_params_key as $i => $key) {
			$route_params[$key] = $route_params_value[$i];
		}

		return $route_params;
	}

	public function resolvedUri()
	{
		$uri = $this->getUri();
		return "/".ltrim(preg_replace("/\=\S+(})/", "$1", $uri), "/");
	}

	public function routeHasRegistered()
	{
		$uri = $this->resolvedUri();
		$routes = Router::getRoutes();
		list($method) = explode('|', $this->getMethodsString(), 1);  

		foreach ($routes as $route) {
			if(ltrim($uri,"/") == ltrim($route->getUri(),"/") && in_array($method, $route->getMethods())) {
				return true;
			}
		}

		return false;
	}

	public static function makeFromRoute(Route $route)
	{
		$route_generator = new static($route->getMethods(), $route->getUri(), $route->getAction());
		return $route_generator;
	}

}