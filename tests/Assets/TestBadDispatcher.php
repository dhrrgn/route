<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace OrnoTest\Assets;

use Orno\Di\ContainerInterface;

class TestBadDispatcher
{
    public function __construct(ContainerInterface $container, array $routes, array $data)
    {
    }

    public function dispatch($method, $uri)
    {
    }
}
