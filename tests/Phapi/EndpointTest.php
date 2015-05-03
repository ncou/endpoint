<?php

namespace Phapi\Tests;

require_once __DIR__. '/TestAssets/BlogEndpoint.php';

use Phapi\Endpoint;
use Phapi\Tests\Endpoint\Asset\Blog;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \Phapi\Endpoint
 */
class EndpointTest extends TestCase
{

    public function testEndpointHead()
    {
        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('withAddedHeader')->andReturnSelf();
        $container = \Mockery::mock('Phapi\Contract\Di\Container');
        $container->shouldReceive('offsetGet')->withArgs(['validHttpVerbs'])->andReturn(['GET', 'POST', 'OPTIONS']);
        $container->shouldReceive('offsetGet')->withArgs(['contentTypes'])->andReturn(['application/json', 'text/json']);
        $container->shouldReceive('offsetGet')->withArgs(['acceptTypes'])->andReturn(['application/json', 'text/json']);

        $endpoint = new Blog($request, $response, $container);
        $this->assertInstanceOf('Phapi\Endpoint', $endpoint);
        $this->assertEquals([], $endpoint->head());
    }

    public function testEndpointHeadException()
    {
        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('withAddedHeader')->andReturnSelf();
        $container = \Mockery::mock('Phapi\Contract\Di\Container');
        $container->shouldReceive('offsetGet')->withArgs(['validHttpVerbs'])->andReturn(['GET', 'POST', 'OPTIONS']);
        $container->shouldReceive('offsetGet')->withArgs(['contentTypes'])->andReturn(['application/json', 'text/json']);
        $container->shouldReceive('offsetGet')->withArgs(['acceptTypes'])->andReturn(['application/json', 'text/json']);

        $endpoint = \Mockery::mock('Phapi\Endpoint')->makePartial();

        $this->assertInstanceOf('Phapi\Endpoint', $endpoint);
        $this->setExpectedException('Phapi\Exception\MethodNotAllowed');
        $this->assertEquals([], $endpoint->head());
    }

    public function testEndpointOptions()
    {
        $expected = [
            'Content-Type' => [
                'application/json',
                'text/json',
            ],
            'Accept' => [
                'application/json',
                'text/json',
            ],
            'methods' => [
                'GET' => [
                    'uri' => '/blog/12',
                    'description' => 'Retrieve the blogs information like id, name and description',
                    'params' => 'id int',
                    'response' => [
                        'id int Blog ID',
                        'name string The name of the blog',
                        'description string A description of the blog',
                        'links string A list of links'
                    ]
                ]
            ]
        ];
        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('withAddedHeader')->andReturnSelf();
        $container = \Mockery::mock('Phapi\Contract\Di\Container');
        $container->shouldReceive('offsetGet')->withArgs(['validHttpVerbs'])->andReturn(['GET', 'POST', 'OPTIONS']);
        $container->shouldReceive('offsetGet')->withArgs(['contentTypes'])->andReturn(['application/json', 'text/json']);
        $container->shouldReceive('offsetGet')->withArgs(['acceptTypes'])->andReturn(['application/json', 'text/json']);

        $endpoint = new Blog($request, $response, $container);
        $this->assertInstanceOf('Phapi\Endpoint', $endpoint);
        $this->assertEquals($expected, $endpoint->options());
    }

    public function testGetResponse()
    {
        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $container = \Mockery::mock('Phapi\Contract\Di\Container');

        $endpoint = new Blog($request, $response, $container);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $endpoint->getResponse());
        $this->assertInstanceOf('Mockery\MockInterface', $endpoint->getResponse());
    }

    public function testResponseNullException()
    {
        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $container = \Mockery::mock('Phapi\Contract\Di\Container');

        $endpoint = new Blog($request, $response, $container);
        $endpoint->changeResponse();
        $this->setExpectedException('Phapi\Exception\MethodNotAllowed');
        $endpoint->options();
    }
}
