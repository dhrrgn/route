<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace OrnoTest\Route;

use Orno\Route\RouteCollection;

class RouteCollectionTest extends \PHPUnit_Framework_Testcase
{
    /**
     * Asserts that routes are set via convenience methods
     *
     * @return void
     */
    public function testSetsRoutesViaConvenienceMethods()
    {
        $router = new RouteCollection;

        $router->get('/route/{wildcard}', 'handler_get', RouteCollection::RESTFUL_STRATEGY);
        $router->post('/route/{wildcard}', 'handler_post', RouteCollection::URI_STRATEGY);
        $router->put('/route/{wildcard}', 'handler_put', RouteCollection::REQUEST_RESPONSE_STRATEGY);
        $router->patch('/route/{wildcard}', 'handler_patch');
        $router->delete('/route/{wildcard}', 'handler_delete');
        $router->head('/route/{wildcard}', 'handler_head');
        $router->options('/route/{wildcard}', 'handler_options');

        $routes = (new \ReflectionClass($router))->getProperty('routes');
        $routes->setAccessible(true);
        $routes = $routes->getValue($router);

        $this->assertCount(7, $routes);
        $this->assertSame($routes['handler_get'], ['strategy' => 1]);
        $this->assertSame($routes['handler_post'], ['strategy' => 2]);
        $this->assertSame($routes['handler_put'], ['strategy' => 0]);
        $this->assertSame($routes['handler_patch'], ['strategy' => 0]);
        $this->assertSame($routes['handler_delete'], ['strategy' => 0]);
        $this->assertSame($routes['handler_head'], ['strategy' => 0]);
        $this->assertSame($routes['handler_options'], ['strategy' => 0]);
    }

    /**
     * Asserts that routes are set via convenience methods with Closures
     *
     * @return void
     */
    public function testSetsRoutesViaConvenienceMethodsWithClosures()
    {
        $router = new RouteCollection;

        $router->get('/route/{wildcard}', function () { return 'get'; });
        $router->post('/route/{wildcard}', function () { return 'post'; });
        $router->put('/route/{wildcard}', function () { return 'put'; });
        $router->patch('/route/{wildcard}', function () { return 'patch'; });
        $router->delete('/route/{wildcard}', function () { return 'delete'; });
        $router->head('/route/{wildcard}', function () { return 'head'; });
        $router->options('/route/{wildcard}', function () { return 'options'; });

        $routes = (new \ReflectionClass($router))->getProperty('routes');
        $routes->setAccessible(true);
        $routes = $routes->getValue($router);

        $this->assertCount(7, $routes);

        foreach ($routes as $route) {
            $this->assertArrayHasKey('callback', $route);
            $this->assertArrayHasKey('strategy', $route);
        }
    }

    /**
     * Asserts that global strategy is used when set
     *
     * @return void
     */
    public function testGlobalStrategyIsUsedWhenSet()
    {
        $router = new RouteCollection;
        $router->setStrategy(RouteCollection::URI_STRATEGY);

        $router->get('/route/{wildcard}', 'handler_get', RouteCollection::RESTFUL_STRATEGY);
        $router->post('/route/{wildcard}', 'handler_post', RouteCollection::URI_STRATEGY);
        $router->put('/route/{wildcard}', 'handler_put', RouteCollection::REQUEST_RESPONSE_STRATEGY);
        $router->patch('/route/{wildcard}', 'handler_patch');
        $router->delete('/route/{wildcard}', 'handler_delete');
        $router->head('/route/{wildcard}', 'handler_head');
        $router->options('/route/{wildcard}', 'handler_options');

        $routes = (new \ReflectionClass($router))->getProperty('routes');
        $routes->setAccessible(true);
        $routes = $routes->getValue($router);

        $this->assertCount(7, $routes);
        $this->assertSame($routes['handler_get'], ['strategy' => 2]);
        $this->assertSame($routes['handler_post'], ['strategy' => 2]);
        $this->assertSame($routes['handler_put'], ['strategy' => 2]);
        $this->assertSame($routes['handler_patch'], ['strategy' => 2]);
        $this->assertSame($routes['handler_delete'], ['strategy' => 2]);
        $this->assertSame($routes['handler_head'], ['strategy' => 2]);
        $this->assertSame($routes['handler_options'], ['strategy' => 2]);
    }

    /**
     * Asserts that `getDispatcher` method returns correct instance
     *
     * @return void
     */
    public function testCollectionReturnsDispatcher()
    {
        $router = new RouteCollection;

        $this->assertInstanceOf('Orno\Route\Dispatcher', $router->getDispatcher());
        $this->assertInstanceOf('FastRoute\Dispatcher\GroupCountBased', $router->getDispatcher());
    }
}
