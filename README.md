# route-generator
Laravel 4.2 Artisan Route and Route Controller Generator

## Installation

* Go to your laravel project directory, run `composer require "emsifa/route-generator:dev-master"`
* In your `app/config/app.php`, add `Emsifa\RouteGenerator\RouteGeneratorServiceProvider` into array `providers`

## Examples

#### Generate simple route

```
php artisan generate:route get user/login UserController@pageLogin
```

Command above will generate a route in your routes file like this:
```php
Route::get('user/login', 'UserController@pageLogin');
```

And also, this command will automatically generate controller `UserController` and append method `pageLogin` into controller if not exists.


#### Generate complex route

```
php artisan generate:route post user/edit/{id_user}/{output?=json} User\\UserController@edit --name="post_edit_user" --before="auth|csrf" --where="id_user:[0-9]+"
```

**Generated route:**

```php
Route::post('/user/edit/{id_user}/{output?}', [
	'as' => 'post_edit_user',
	'before' => 'auth|csrf',
	'uses' => 'User\UserController@edit'
	])
	->where('id_user', '[0-9]+');
```

**Generated controller and method:**

```php
<?php namespace User;

use BaseController;

//# Used facades
use URL;
use View;
use Input;
use Config;
use Session;
use Response;
use Redirect;

//# Used models
use User;

class UserController extends BaseController {

	/**
	 * @name	post_edit_user
	 * @route	POST /user/edit/{id_user}/{output?}
	 * @before	auth|csrf
	 * -------------------------------
	 * @param	string $id_user [0-9]+
	 * @param	string $output
	 */
	public function edit($id_user, $output = 'json')
	{
		throw new \Exception('Edit me at "app/controllers/User/UserController.php" dude!');
	}

}
```

## Generate route actions from routes file

Second command from this package is `generate:route-actions`. 
This command will generate Controllers and Methods like example above from registered routes.