<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace Orno\Route;

use FastRoute;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use Orno\Di\ContainerInterface;
use Orno\Http\Exception as HttpException;
use Orno\Http\JsonResponse;
use Orno\Http\Response;
use Orno\Http\ResponseInterface;
use Orno\Route\RouteCollection;

class Dispatcher extends GroupCountBasedDispatcher implements RouteStrategyInterface
{
    /**
     * Route strategy functionality
     */
    use RouteStrategyTrait;

    /**
     * Constructor
     *
     * @param array $routes
     * @param array $data
     */
    public function __construct(ContainerInterface $container, array $routes, array $data)
    {
        $this->container = $container;
        $this->routes    = $routes;

        parent::__construct($data);
    }

    /**
     * Match and dispatch a route matching the given http method and uri
     *
     * @param  string $method
     * @param  string $uri
     * @return \Orno\Http\ResponseInterface
     */
    public function dispatch($method, $uri)
    {
        $match = parent::dispatch($method, $uri);

        switch ($match[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                $response = $this->handleNotFound();
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowed  = $match[1];
                $response = $this->handleNotAllowed($allowed);
                break;
            case FastRoute\Dispatcher::FOUND:
                $handler  = (isset($this->routes[$match[1]]['callback'])) ? $this->routes[$match[1]]['callback'] : $match[1];
                $strategy = $this->routes[$match[1]]['strategy'];
                $vars     = $match[2];
                $response = $this->handleFound($handler, $strategy, $vars);
                break;
        }

        return $response;
    }

    /**
     * Handle dispatching of a found route
     *
     * @param  string|\Closure $handler
     * @param  integer         $strategy
     * @param  array           $vars
     * @return \Orno\Http\ResponseInterface
     */
    protected function handleFound($handler, $strategy, array $vars)
    {
        $strategy = (is_null($this->strategy)) ? $strategy : $this->strategy;

        // figure out what the controller is
        if (($handler instanceof \Closure) || (is_string($handler) && is_callable($handler))) {
            $controller = $handler;
        }

        if (is_string($handler) && strpos($handler, '::') !== false) {
            $controller = explode('::', $handler);
        }

        // handle getting of response based on strategy
        switch ($strategy) {
            case RouteStrategyInterface::REQUEST_RESPONSE_STRATEGY:
                $response = $this->handleRequestResponseStrategy($controller);
                break;
            case RouteStrategyInterface::RESTFUL_STRATEGY:
                $response = $this->handleRestfulStrategy($controller);
                break;
            case RouteStrategyInterface::URI_STRATEGY:
                $response = $this->handleUriStrategy($controller, $vars);
                break;
        }

        return $response;
    }

    /**
     * Invoke a controller action
     *
     * @param  string|\Closure $controller
     * @param  array           $vars
     * @return \Orno\Http\ResponseInterface
     */
    public function invokeController($controller, array $vars = [])
    {
        if (is_array($controller)) {
            $controller = [
                $this->container->get($controller[0]),
                $controller[1]
            ];
        }

        return call_user_func_array($controller, array_values($vars));
    }

    /**
     * Handles response to Request -> Response Strategy based routes
     *
     * @param  string|\Closure $controller
     * @return \Orno\Http\ResponseInterface
     */
    protected function handleRequestResponseStrategy($controller)
    {
        $response = $this->invokeController($controller, [
            $this->container->get('Orno\Http\Request'),
            $this->container->get('Orno\Http\Response')
        ]);

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        throw new \RuntimeException(
            'When using the Request -> Response Strategy your controller must return an instance of [Orno\Http\ResponseInterface]'
        );
    }

    /**
     * Handles response to Restful Strategy based routes
     *
     * @param  string|\Closure $controller
     * @return \Orno\Http\ResponseInterface
     */
    protected function handleRestfulStrategy($controller)
    {
        try {
            $response = $this->invokeController($controller, [
                $this->container->get('Orno\Http\Request')
            ]);

            if ($response instanceof JsonResponse) {
                return $response;
            }

            if (is_array($response) || $response instanceof \ArrayObject) {
                return new JsonResponse($response);
            }

            throw new \RuntimeException(
                'Your controller action must return a valid response for the Restful Strategy. ' .
                'Acceptable responses are of type: [Array], [ArrayObject] and [Orno\Http\JsonResponse]'
            );
        } catch (HttpException $e) {
            return $e->getJsonResponse();
        }
    }

    /**
     * Handles response to URI Strategy based routes
     *
     * @param  string|\Closure $controller
     * @param  array           $vars
     * @return \Orno\Http\ResponseInterface
     */
    protected function handleUriStrategy($controller, array $vars)
    {
        $response = $this->invokeController($controller, $vars);

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        try {
            $response = new Response($response);
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to build Response from controller return value', 0, $e);
        }

        return $response;
    }

    /**
     * Handle a not found route
     *
     * @return \Orno\Http\ResponseInterface
     */
    protected function handleNotFound()
    {
        $exception = new HttpException\NotFoundException;

        if ($this->getStrategy() === RouteStrategyInterface::RESTFUL_STRATEGY) {
            return $exception->getJsonResponse();
        }

        throw $exception;
    }

    /**
     * Handles a not allowed route
     *
     * @param  array $allowed
     * @return \Orno\Http\ResponseInterface
     */
    protected function handleNotAllowed(array $allowed)
    {
        $exception = new HttpException\MethodNotAllowedException($allowed);

        if ($this->getStrategy() === RouteStrategyInterface::RESTFUL_STRATEGY) {
            return $exception->getJsonResponse();
        }

        throw $exception;
    }
}
