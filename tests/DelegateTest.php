<?php

/*
 * This File is part of the Lucid\Infusion package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Tests\Infusion;

use Zend\Diactoros\Response;
use Lucid\Infusion\Delegate;
use Lucid\Infusion\Middlewares;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;

/**
 * Class DelegateTest
 * @package Lucid\Infusion
 * @author Thomas Appel <mail@thomas-appel.com>
 */
class DelegateTest extends TestCase
{
    /** @test */
    public function itShouldImplementDelegateInterface()
    {
        $this->assertInstanceOf(
            DelegateInterface::class,
            new Delegate(new Middlewares([]), $this->mockResponseFactory())
        );
    }

    /** @test */
    public function itShouldDelegateToNextMiddleware()
    {
        list ($a, $b, $c) = $this->mockMiddlewares(3);
        $delegate = new Delegate(
            new Middlewares([$a, $b, $c]),
            $factory = $this->mockResponseFactory()
        );

        foreach ([$a, $b] as $mw) {
            $mw->expects($this->exactly(2))->method('process')->willReturnCallback(
                function (ServerRequestInterface $req, DelegateInterface $delegate) {
                    return $delegate->process($req);
                }
            );
        }

        $c->expects($this->exactly(2))
            ->method('process')
            ->willReturn($response = $this->getMockForAbstractClass(ResponseInterface::class));

        /** @var ServerRequestInterface $request */
        $request = $this->getMockForAbstractClass(ServerRequestInterface::class);

        $this->assertInstanceOf(ResponseInterface::class, $delegate->process($request));
        $this->assertSame($response, $delegate->process($request));
    }

    /** @test */
    public function delegateShouldReturnErrorResponse()
    {
        $delegate = new Delegate(
            new Middlewares([]),
            $factory = $this->mockResponseFactory()
        );

        /** @var ServerRequestInterface $request */
        $request = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $factory->expects($this->once())->method('createResponse')->willReturn(new Response());

        $this->assertInstanceOf(ResponseInterface::class, $response = $delegate->process($request));
        $this->assertSame(444, $response->getStatusCode());
    }

    private function mockMiddlewares($count) : array
    {
        return array_map(function () {
            return $this->mockMiddleware();
        }, range(0, $count));
    }

    /**
     * @return MiddlewareInterface
     */
    private function mockMiddleware() : MiddlewareInterface
    {
        return $this->getMockForAbstractClass(MiddlewareInterface::class);
    }

    private function mockResponseFactory() : ResponseFactoryInterface
    {
        return $this->getMockForAbstractClass(ResponseFactoryInterface::class);
    }
}
