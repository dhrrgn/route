<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace Orno\Route;

use Orno\Di\ContainerInterface;

interface DispatcherInterface
{
    /**
     * Constructor
     *
     * @param array $routes
     * @param array $data
     */
    public function __construct(ContainerInterface $container, array $routes, array $data);

    /**
     * Match and dispatch a route matching the given http method and uri
     *
     * @param  string $method
     * @param  string $uri
     * @return \Orno\Http\ResponseInterface
     */
    public function dispatch($method, $uri);
}
