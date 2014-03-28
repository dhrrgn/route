<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace OrnoTest\Assets;

use Orno\Route\DispatcherInterface;
use Orno\Di\ContainerInterface;

class TestDispatcher implements DispatcherInterface
{
    public function __construct(ContainerInterface $container, array $routes, array $data)
    {
    }

    public function dispatch($method, $uri)
    {
    }
}
