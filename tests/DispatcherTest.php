<?php
/**
 * The Orno Component Library
 *
 * @author  Phil Bennett @philipobenito
 * @license MIT (see the LICENSE file)
 */
namespace OrnoTest\Route;

use Orno\Http\Exception as HttpException;
use Orno\Http\Request;
use Orno\Http\Response;
use Orno\Http\JsonResponse;
use Orno\Route;

class DispatcherTest extends \PHPUnit_Framework_Testcase
{
    /**
     * Assert that a route using the Restful Strategy returns a json response
     * when a http exception is thrown
     *
     * @return void
     */
    public function testRestfulStrategyReturnsJsonResponseWhenHttpExceptionIsThrown()
    {
        $controller = $this->getMock('SomeClass', ['someMethod']);
        $controller->expects($this->once())
                   ->method('someMethod')
                   ->will($this->throwException(new HttpException\ConflictException));

        $container = $this->getMock('Orno\Di\Container');
        $container->expects($this->at(1))
                  ->method('get')
                  ->with($this->equalTo('SomeClass'))
                  ->will($this->returnValue($controller));

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(Route\RouteStrategyInterface::RESTFUL_STRATEGY);
        $collection->get('/route', 'SomeClass::someMethod');

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Orno\Http\JsonResponse', $response);
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('{"status_code":409,"message":"Conflict"}', $response->getContent());
    }

    /**
     * Assert that a route using Restful Strategy throws exception for wrong response type
     *
     * @return void
     */
    public function testRestfulStrategyRouteThrowsExceptionWhenWrongResponseReturned()
    {
        $this->setExpectedException('RuntimeException', 'Your controller action must return a valid response for the Restful Strategy. Acceptable responses are of type: [Array], [ArrayObject] and [Orno\Http\JsonResponse]');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(Route\RouteStrategyInterface::RESTFUL_STRATEGY);
        $collection->get('/route', function ($response) {
            return new \stdClass;
        });

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Assert that a route using the Restful Strategy gets passed the correct arguments
     *
     * @return void
     */
    public function testRestfulStrategyReceivesCorrectArguments()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(Route\RouteStrategyInterface::RESTFUL_STRATEGY);
        $collection->get('/route', function (Request $request) {
            $this->assertInstanceOf('Orno\Http\RequestInterface', $request);

            return new \ArrayObject;
        });

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Orno\Http\ResponseInterface', $response);
    }

    /**
     * Assert that a route using the Restful Strategy returns response when controller does
     *
     * @return void
     */
    public function testRestfulStrategyRouteReturnsResponseWhenControllerDoes()
    {
        $mockResponse = $this->getMock('Orno\Http\JsonResponse');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(Route\RouteStrategyInterface::RESTFUL_STRATEGY);
        $collection->get('/route/{id}/{name}', function (Request $request) use ($mockResponse) {
            $this->assertInstanceOf('Orno\Http\RequestInterface', $request);

            return $mockResponse;
        });

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route/2/phil');

        $this->assertSame($mockResponse, $response);
    }

    /**
     * Asserts that the correct method is invoked on a class based controller
     *
     * @return void
     */
    public function testClassBasedControllerInvokesCorrectMethod()
    {
        $controller = $this->getMock('SomeClass', ['someMethod']);
        $controller->expects($this->once())
                   ->method('someMethod')
                   ->with($this->equalTo('2'), $this->equalTo('phil'))
                   ->will($this->returnValue('hello world'));

        $container = $this->getMock('Orno\Di\Container');
        $container->expects($this->once())
                  ->method('get')
                  ->with($this->equalTo('SomeClass'))
                  ->will($this->returnValue($controller));

        $collection = new Route\RouteCollection($container);
        $collection->setStrategy(Route\RouteStrategyInterface::URI_STRATEGY);
        $collection->get('/route/{id}/{name}', 'SomeClass::someMethod');

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route/2/phil');
        $this->assertEquals('hello world', $response->getContent());
    }

    /**
     * Assert that a route using the URI Strategy gets passed the correct arguments
     *
     * @return void
     */
    public function testUriStrategyRouteReceivesCorrectArguments()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(Route\RouteStrategyInterface::URI_STRATEGY);
        $collection->get('/route/{id}/{name}', function ($id, $name) {
            $this->assertEquals('2', $id);
            $this->assertEquals('phil', $name);
        });

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route/2/phil');
    }

    /**
     * Assert that a route using the URI Strategy returns response when controller does
     *
     * @return void
     */
    public function testUriStrategyRouteReturnsResponseWhenControllerDoes()
    {
        $mockResponse = $this->getMock('Orno\Http\Response');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(Route\RouteStrategyInterface::URI_STRATEGY);
        $collection->get('/route/{id}/{name}', function ($id, $name) use ($mockResponse) {
            $this->assertEquals('2', $id);
            $this->assertEquals('phil', $name);

            return $mockResponse;
        });

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route/2/phil');

        $this->assertSame($mockResponse, $response);
    }

    /**
     * Assert that a route using the URI Strategy throws exception when Response
     * cannot be built
     *
     * @return void
     */
    public function testUriStrategyRouteThrowsExceptionWhenResponseCannotBeBuilt()
    {
        $this->setExpectedException('RuntimeException', 'Unable to build Response from controller return value');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(Route\RouteStrategyInterface::URI_STRATEGY);
        $collection->get('/route/{id}/{name}', function ($id, $name) {
            $this->assertEquals('2', $id);
            $this->assertEquals('phil', $name);

            return new \stdClass;
        });

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route/2/phil');
    }

    /**
     * Assert that a route using the Request -> Response Strategy gets passed the correct arguments
     *
     * @return void
     */
    public function testRequestResponseStrategyRouteReceivesCorrectArguments()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(Route\RouteStrategyInterface::REQUEST_RESPONSE_STRATEGY);
        $collection->get('/route', function (Request $request, Response $response) {
            $this->assertInstanceOf('Orno\Http\RequestInterface', $request);
            $this->assertInstanceOf('Orno\Http\ResponseInterface', $response);

            return $response;
        });

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Orno\Http\ResponseInterface', $response);
    }

    /**
     * Assert that a route using the Request -> Response Strategy throws exception
     * when correct response not returned
     *
     * @return void
     */
    public function testRequestResponseStrategyRouteThrowsExceptionWhenWrongResponseReturned()
    {
        $this->setExpectedException('RuntimeException', 'When using the Request -> Response Strategy your controller must return an instance of [Orno\Http\ResponseInterface]');

        $collection = new Route\RouteCollection;
        $collection->setStrategy(Route\RouteStrategyInterface::REQUEST_RESPONSE_STRATEGY);
        $collection->get('/route', function (Request $request, Response $response) {
            $this->assertInstanceOf('Orno\Http\RequestInterface', $request);
            $this->assertInstanceOf('Orno\Http\ResponseInterface', $response);

            return [];
        });

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Asserts that a 404 response is returned whilst using restful strategy
     *
     * @return void
     */
    public function testDispatcherHandles404CorrectlyOnRestfulStrategy()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(Route\RouteStrategyInterface::RESTFUL_STRATEGY);

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Orno\Http\JsonResponse', $response);
        $this->assertSame('{"status_code":404,"message":"Not Found"}', $response->getContent());
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * Asserts that a 404 exception is thrown whilst using standard strategies
     *
     * @return void
     */
    public function testDispatcherHandles404CorrectlyOnStandardStrategies()
    {
        $this->setExpectedException('Orno\Http\Exception\NotFoundException', 'Not Found', 0);

        $collection = new Route\RouteCollection;

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route');
    }

    /**
     * Asserts that a 405 response is returned whilst using restful strategy
     *
     * @return void
     */
    public function testDispatcherHandles405CorrectlyOnRestfulStrategy()
    {
        $collection = new Route\RouteCollection;
        $collection->setStrategy(Route\RouteStrategyInterface::RESTFUL_STRATEGY);
        $collection->post('/route', 'handler');
        $collection->put('/route', 'handler');
        $collection->delete('/route', 'handler');

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route');

        $this->assertInstanceOf('Orno\Http\JsonResponse', $response);
        $this->assertSame('{"status_code":405,"message":"Method Not Allowed"}', $response->getContent());
        $this->assertSame(405, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Allow'));
        $this->assertSame('POST, PUT, DELETE', $response->headers->get('Allow'));
    }

    /**
     * Asserts that a 405 exception is thrown whilst using standard strategies
     *
     * @return void
     */
    public function testDispatcherHandles405CorrectlyOnStandardStrategies()
    {
        $this->setExpectedException('Orno\Http\Exception\MethodNotAllowedException', 'Method Not Allowed', 0);

        $collection = new Route\RouteCollection;
        $collection->post('/route', 'handler');
        $collection->put('/route', 'handler');
        $collection->delete('/route', 'handler');

        $dispatcher = $collection->getDispatcher();

        $response = $dispatcher->dispatch('GET', '/route');
    }
}
