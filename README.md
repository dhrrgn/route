# Orno\Route

[![Build Status](https://travis-ci.org/orno/route.png?branch=master)](https://travis-ci.org/orno/route)
[![Code Coverage](https://scrutinizer-ci.com/g/orno/route/badges/coverage.png?s=79362898649cdc823bebfc309db57306debb3673)](https://scrutinizer-ci.com/g/orno/route/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/orno/route/badges/quality-score.png?s=a11cf213102e6a0c37b81ce60401ecf2a9555a51)](https://scrutinizer-ci.com/g/orno/route/)
[![Latest Stable Version](https://poser.pugx.org/orno/route/v/stable.png)](https://packagist.org/packages/orno/route)
[![Total Downloads](https://poser.pugx.org/orno/route/downloads.png)](https://packagist.org/packages/orno/route)

Orno\Route is a fast routing/dispatcher package enabling you to build well designed performant web apps. At it's core is Nikita Popov's [FastRoute](https://github.com/nikic/FastRoute) package allowing this package to concentrate on the dispatch of your controllers.

## Installation

Add `orno/route` to your `composer.json`.

```json
{
    "require": {
        "orno/route": "1.*"
    },
    "minimum-stability": "dev"
}
```

Allow Composer to autoload the package.

```php
<?php

include 'vendor/autoload.php';
```

## Usage

- [Basic Usage](#basic-usage)
- [Controller Types](#controller-types)
    - [Class Methods](#class-methods)
        - [Dependency Injection](#dependency-injection)
    - [Anonymous Functions/Closures](#anonymous-functionsclosures)
    - [Named Functions](#name-functions)
- [Wildcard Routes](#wildcard-routes)
- [Request Methods](#request-methods)

### Basic Usage

By default when dispatching your controllers, Orno\Route will employ the `Request -> Response Strategy` (more on strategies later). This strategy will provide you with a request and response object with which you can pull data from the request, manipulate the response and return it.

```php
use Orno\Http\Request;
use Orno\Http\Response;

$router = new Orno\Route\RouteCollection;

$router->addRoute('GET', '/acme/route', function (Request $request, Response $response) {
    // do some clever shiz
    return $response;
});

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/route');

$response->send();
```

### Controller Types

Orno\Route will allow you to use any `callable` as a controller.

#### Class Methods

```php
namespace Acme;

use Orno\Http\Request;
use Orno\Http\Response;

class Controller
{
    public function action (Request $request, Response $response)
    {
        // do some clever shiz
        return $response;
    }
}
```

```php
$router = new Orno\Route\RouteCollection;

$router->addRoute('GET', '/acme/route', 'Acme\Controller::action');

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/route');

$response->send();
```

##### Dependency Injection

Controller classes are resolved through [Orno\Di](https://github.com/orno/di) so if your class has shared dependencies between methods you can have said dependencies injected in to the class contructor. For more information on using [Orno\Di](https://github.com/orno/di), check out the [documentation](https://github.com/orno/di).

Once you have a configured Container, it is as simple as injecting it in to the `RouteCollection`.

```php
$container = new Orno\Di\Container;
// ... set up the container

$router = new Orno\Route\RouteCollection($container);
// ... handle routing
```

#### Anonymous Functions/Closures

```php
use Orno\Http\Request;
use Orno\Http\Response;

$router = new Orno\Route\RouteCollection;

$router->addRoute('GET', '/acme/route', function (Request $request, Response $response) {
    // do some clever shiz
    return $response;
});

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/route');

$response->send();
```

#### Named Functions

```php
use Orno\Http\Request;
use Orno\Http\Response;

function controllerAction (Request $request, Response$response) {
    // do some clever shiz
    return $response
}

$router = new Orno\Route\RouteCollection;

$router->addRoute('GET', '/acme/route', 'controllerAction');

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/route');

$response->send();
```

### Wildcard Routes

Wilcard routes allow a route to respond to dynamic parts of a URI. If a route has dynamic parts, they will be passed in to the controller as an associative array of arguments.

```php
use Orno\Http\Request;
use Orno\Http\Response;

$router = new Orno\Route\RouteCollection;

$router->addRoute('GET', '/user/{id}/{name}', function (Request $request, Response $response, array $args) {
    // $args = [
    //     'id'   => {id},  // the actual value of {id}
    //     'name' => {name} // the actual value of {name}
    // ];

    return $response;
});

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/1/phil');

$response->send();
```

Dynamic parts of a URI can also be limited to match certain requirements.

```php
use Orno\Http\Request;
use Orno\Http\Response;

$router = new Orno\Route\RouteCollection;

// this route will only match if {id} is a number and {name} is a word
$router->addRoute('GET', '/user/{id:number}/{name:word}', function (Request $request, Response $response, array $args) {
    // do some clever shiz
    return $response;
});

$dispatcher = $router->getDispatcher();

$response = $dispatcher->dispatch('GET', '/acme/1/phil');

$response->send();
```

Dynamic parts can also be set as any regular expression such as `{id:[0-9]+}`.

### Request Methods

The router has convenience methods for setting routes that will respond differently depending on the HTTP request method.

```php
$router = new Orno\Route\RouteCollection;

$router->get('/acme/route', 'Acme\Controller::getMethod');
$router->post('/acme/route', 'Acme\Controller::postMethod');
$router->put('/acme/route', 'Acme\Controller::putMethod');
$router->patch('/acme/route', 'Acme\Controller::patchMethod');
$router->delete('/acme/route', 'Acme\Controller::deleteMethod');
$router->head('/acme/route', 'Acme\Controller::headMethod');
$router->options('/acme/route', 'Acme\Controller::optionsMethod');
```

Each of the above routes will respond to the same URI but will invoke a different callable based on the HTTP request method.
