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
- [Strategies](#strategies)
    - [Request -> Response Strategy](#request---response-strategy)
    - [URI Strategy](#uri-strategy)
    - [RESTful Strategy](#restful-strategy)
        - [Pre-built JSON Resopnses](#pre-built-json-responses)
            - [Available JSON Responses](#available-json-responses)
        - [HTTP 4xx Exceptions](#http-4xx-exceptions)
            - [Available HTTP Exceptions](#available-http-exceptions)
- [Considerations](#considerations)

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

### Strategies

Route strategies are a way of encouraging good design based on the type of application you are building. Available strategies are as follows.

- `Orno\Route\RouteStrategyInterface::REQUEST_RESPONSE_STRATEGY`
- `Orno\Route\RouteStrategyInterface::RESTFUL_STRATEGY`
- `Orno\Route\RouteStrategyInterface::URI_STRATEGY`

Strategies can be set individually per route by passing in one of the above constants as the last argument of your route definition.

```php
use Orno\Route\RouteStrategyInterface;

$router = new Orno\Route\RouteCollection;

$router->addRoute('GET', '/acme/route', 'Acme\Controller::action', RouteStrategyInterface::REQUEST_RESPONSE_STRATEGY);
$router->get('/acme/route', 'Acme\Controller::action', RouteStrategyInterface::URI_STRATEGY);
$router->put('/acme/route', 'Acme\Controller::action', RouteStrategyInterface::RESTFUL_STRATEGY);
```

Or a global strategy can be set to be used by all routes in a specific collection.

```php
use Orno\Route\RouteStrategyInterface;

$router = new Orno\Route\RouteCollection;
$router->setStrategy(RouteStrategyInterface::RESTFUL_STRATEGY);
```

#### Request -> Response Strategy

The Request -> Response Strategy is used by default and provides the controller with both the `Request` and `Response` objects. The idea here being that you can pull any information you need from the `Request`, manipulate the `Response` and return it for the dispatcher to send to the browser. The dispatcher will throw a `RuntimeException` if the controller it is invoking does not return an instance of the `Response` object.

```php
$route->get('/acme/route', function (Request $request, Response $response) {
    // retrieve data from $request, do what you need to do and build your $content

    $response->setContent($content);
    $response->setStatusCode(200);

    return $response;
});
```

```php
$route->put('/user/{id}', function (Request $request, Response $response, array $args) {
    $userId = $args['id'];
    $requestBody = json_decode($request->getContent(), true);

    // possibly update a record in the database with the request body

    $request->setContent('Updated User with ID: ' . $userId);
    $request->setStatusCode(202);

    return $response;
});
```

Whilst these are primitive and naive examples, it is good design to handle your request and response lifecycle in this way as you are fully in control of input and output.

#### URI Strategy

The URI Strategy is a simpler strategy aimed at smaller applications. It makes no assumptions about how your controller is built. The only arguments passed to your controller will be the values of any wildcard parts of your routes string if any exist. It expects a value to be returned but this can any type of `Response` based object that can be sent to the browser or a string that a response can be built from.

```php
$route->get('/hello/{name1}/{name2}', function ($name1, $name2) {
    return '<h1>Hello ' . $name1 . ' and ' . $name2 . '</h1>';
});
```

#### RESTful Strategy

The RESTful Strategy is aimed at making life a little but easier when building RESTful APIs. When using this strategy a `Request` object will be passed in to your callable along with an optional array of named wildcard route values.

It is expected that a `Response` object or data of a type that can be converted to JSON is returned.

```php
// this route would be considered a "get all" resource
$route->get('/acme', function (Request $request) {
    // pull data from $request and do some shiz

    return [
        // ... data to be converted to json
    ];
});

// this route would be considered a "get one" resource
$route->get('/acme/{id}', function (Request $request, array $args) {
    // get any required data from $request and find enitity relating to $args['id']

    return [
        // ... data to be converted to json
    ];
});
```

The problem with returning an array is that you are always assuming a `200 OK` HTTP response code.

#### Pre-built JSON Resopnses

[Orno\Http](https://github.com/orno/http) provides several pre-built JSON `Response` objects that are pre-configured and will handle the response for you.

For example, when creating a resource, on sucess we would likely return a `201 Created` response. This can be done very easily.

```php
use Orno\Http\JsonResponse\CreatedJsonResponse;

$route->post('/acme', function (Request $request) {
    // create a record from the $request body

    return new CreatedJsonResponse([
        // ... data to be converted to json
    ]);
});
```

The above route will return a response with the correct `201` status code and a body JSON converted from the array passed in to the response.

##### Available JSON Responses

| Response Object                                                  | Status Code | Notes                   |
| ---------------------------------------------------------------- | ----------- | ----------------------- |
| `Orno\Http\JsonResponse`                                         | 200         |                         |
| `Orno\Http\JsonResponse\CreatedJsonResponse`                     | 201         |                         |
| `Orno\Http\JsonResponse\AcceptedJsonResponse`                    | 202         |                         |
| `Orno\Http\JsonResponse\NonAuthoritativeInformationJsonResponse` | 203         |                         |
| `Orno\Http\JsonResponse\NoContentJsonResponse`                   | 204         | Will not return a body. |
| `Orno\Http\JsonResponse\ResetContentJsonResponse`                | 205         |                         |
| `Orno\Http\JsonResponse\PartialContentJsonResponse`              | 206         |                         |

#### HTTP 4xx Exceptions

In a RESTful API, covering all outcomes and returning the correct 4xx response can become quite verbose. Therefore, the dispatcher provides a convenient way to ensure you can return the correct response without the need for a conditional being created for every outcome.

Simply throw one of the HTTP exceptions from within your application layer and the dispatcher will catch the exception and build the appropriate response.

```php
use Orno\Http\Exception\BadRequestException;

$route->post('/acme', function (Request $request) {
    // create a record from the $request body

    // if we fail to insert due to a bad request
    throw new BadRequestException;

    // ...
});
```

If the exception is thrown, a request with the correct response code and headers is built containing the following body.

```json
{
    "status_code": 400,
    "message": "Bad Request"
}
```

##### Available HTTP Exceptions

| Status Code | Exception                                           | Description                                                                                                                                                                                                  |
| ----------- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 400         | `Orno\Http\Exception\BadRequestException`           | The request cannot be fulfilled due to bad syntax.                                                                                                                                                           |
| 401         | `Orno\Http\Exception\UnauthorizedException`         | Similar to 403 Forbidden, but specifically for use when authentication is required and has failed or has not yet been provided.                                                                              |
| 403         | `Orno\Http\Exception\ForbiddenException`            | The request was a valid request, but the server is refusing to respond to it.                                                                                                                                |
| 404         | `Orno\Http\Exception\NotFoundException`             | The requested resource could not be found but may be available again in the future.                                                                                                                          |
| 405         | `Orno\Http\Exception\MethodNotAllowedException`     | A request was made of a resource using a request method not supported by that resource; for example, using GET on a form which requires data to be presented via POST, or using PUT on a read-only resource. |
| 406         | `Orno\Http\Exception\NotAcceptableException`        | The requested resource is only capable of generating content not acceptable according to the Accept headers sent in the request.                                                                             |
| 409         | `Orno\Http\Exception\ConflictException`             | Indicates that the request could not be processed because of conflict in the request, such as an edit conflict in the case of multiple updates.                                                              |
| 410         | `Orno\Http\Exception\GoneException`                 | Indicates that the resource requested is no longer available and will not be available again.                                                                                                                |
| 411         | `Orno\Http\Exception\LengthRequiredException`       | The request did not specify the length of its content, which is required by the requested resource.                                                                                                          |
| 412         | `Orno\Http\Exception\PreconditionFailedException`   | The server does not meet one of the preconditions that the requester put on the request.                                                                                                                     |
| 415         | `Orno\Http\Exception\UnsupportedMediaException`     | The request entity has a media type which the server or resource does not support.                                                                                                                           |
| 417         | `Orno\Http\Exception\ExpectationFailedException`    | The server cannot meet the requirements of the Expect request-header field.                                                                                                                                  |
| 418         | `Orno\Http\Exception\ImATeapotException`            | [I'm a teapot](http://en.wikipedia.org/wiki/April_Fools%27_Day_RFC).                                                                                                                                         |
| 428         | `Orno\Http\Exception\PreconditionRequiredException` | The origin server requires the request to be conditional.                                                                                                                                                    |
| 429         | `Orno\Http\Exception\TooManyRequestsException`      | The user has sent too many requests in a given amount of time.                                                                                                                                               |

### Considerations

The main thing to consider when you are building your controllers is that the dispatcher does not handle any output buffering, it is a Response based dispatcher and will do it's best to build a response based on what is returned from your controller before sending that Response to the browser, however, if you are outputting from your controller, it will output as the code is run and could cause problems with the setting of any header based values.
