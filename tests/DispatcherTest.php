<?php

/*
 * This File is part of the Lucid\Infusion package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Infusion\Tests;

use Lucid\Infusion\Dispatcher;
use Psr\Http\Middleware\StackInterface;
use SebastianBergmann\PeekAndPoke\Proxy;
use Http\Factory\Diactoros\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Middleware\DelegateInterface;
use Psr\Http\Middleware\MiddlewareInterface;
use Psr\Http\Middleware\ServerMiddlewareInterface;

/**
 * Class DispatcherTest
 * @package Lucid\Infusion
 * @author  Thomas Appel <mail@thomas-appel.com>
 */
class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function itShouldBeInstantiable()
    {
        $this->assertInstanceOf(StackInterface::class, new Dispatcher(new ResponseFactory()));
    }

    /** @test */
    public function itShouldReturnNewInstanceWhenAddingMiddleware()
    {
        $req = $this->getMockBuilder(RequestInterface::class)->getMock();
        $res = $this->getMockBuilder(ResponseInterface::class)->getMock();

        $m1 = $this->mockMiddleware($req, $res);
        $m2 = $this->mockMiddleware($req, $res);
        $m3 = $this->mockMiddleware($req, $res);

        $dispatcher = new Dispatcher(new ResponseFactory, []);
        $this->assertFalse($dispatcher === ($dispatcher2 = $dispatcher->withMiddleware($m1)));
        $this->assertFalse($dispatcher2 === ($dispatcher3 = $dispatcher2->withMiddleware($m2)));
        $this->assertFalse($dispatcher3 === ($dispatcher4 = $dispatcher3->withMiddleware($m3)));

        $proxy = new Proxy($dispatcher4);
        $this->assertEquals(3, count($proxy->middleware));
    }

    /** @test */
    public function itShouldReturnNewInstanceWhenRemovingMiddleware()
    {
        $req = $this->getMockBuilder(RequestInterface::class)->getMock();
        $res = $this->getMockBuilder(ResponseInterface::class)->getMock();

        $m1 = $this->mockMiddleware($req, $res);
        $m2 = $this->mockMiddleware($req, $res);
        $m3 = $this->mockMiddleware($req, $res);

        $dispatcher = new Dispatcher(new ResponseFactory, [$m1, $m2, $m3]);
        $this->assertFalse($dispatcher === ($dispatcher2 = $dispatcher->withoutMiddleware($m2)));

        $proxy = new Proxy($dispatcher2);
        $this->assertEquals(2, count($proxy->middleware));

        $this->assertFalse($dispatcher2 === ($dispatcher3 = $dispatcher2->withoutMiddleware($m1)));

        $proxy = new Proxy($dispatcher3);
        $this->assertEquals(1, count($proxy->middleware));
    }

    /** @test */
    public function itShouldReturn444ResponseIfNoResponseWasCreatedByAnyMiddleware()
    {
        /** @var RequestInterface $req */
        $req = $this->getMockBuilder(RequestInterface::class)->getMock();
        /** @var ResponseInterface $res */
        $res = $this->getMockBuilder(ResponseInterface::class)->getMock();

        $m1 = $this->mockMiddleware($req, $res);
        $m2 = $this->mockMiddleware($req, $res);
        $m3 = $this->mockMiddleware($req, $res);

        $dispatcher = new Dispatcher(new ResponseFactory, [$m1, $m2, $m3]);
        $response = $dispatcher->process($req);

        $this->assertSame(444, $response->getStatusCode());
    }

    /** @test */
    public function itShouldDelegatesMiddleware()
    {
        $req = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $res = $this->getMockBuilder(ResponseInterface::class)->getMock();

        $m = new class($this) implements ServerMiddlewareInterface
        {
            public $name;
            public $code = 200;
            private $unit;

            public function __construct(\PHPUnit_Framework_TestCase $unit)
            {
                $this->unit = $unit;
            }

            public function process(
                ServerRequestInterface $request,
                DelegateInterface $frame
            ) : ResponseInterface {
                $pr = $frame->next($request);
                $res = (new ResponseFactory())->createResponse($this->code);

                return $res->withAddedHeader(
                    'X-Middleware-Name',
                    $pr->getHeaderLine('X-Middleware-Name') . $this->name
                );
            }
        };

        $m1 = clone $m;
        $m1->name = 'A';
        $m1->code = 300;
        $m2 = clone $m;
        $m2->name = 'B';
        $m2->code = 202;
        $m3 = clone $m;
        $m3->name = 'C';
        $m3->code = 404;

        $dispatcher = new Dispatcher(new ResponseFactory, [$m1, $m2, $m3]);
        $response = $dispatcher->process($req);
        $this->assertSame('ABC', $response->getHeaderLine('X-Middleware-Name'));
        $this->assertSame(404, $dispatcher->process($req)->getStatusCode());
    }

    private function mockMiddleware(RequestInterface $request, ResponseInterface $response, \Closure $ret = null)
    {
        $mw = $this->getMockBuilder(MiddlewareInterface::class)->setMethods(['process'])->getMock();
        $mw->method('process')->willReturnCallback($ret ?: function ($req, $next) {
            return $next->next($req);
        });

        return $mw;
    }
}
