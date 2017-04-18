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

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Lucid\Infusion\Middlewares;
use Lucid\Infusion\PoolInterface;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Comparator\MockObjectComparator;

class MiddlewaresTest extends TestCase
{
    /** @test */
    public function itShouldImplementPoolInterface()
    {
        $this->assertInstanceOf(PoolInterface::class, new Middlewares([]));
    }

    /** @test */
    public function itShouldRequireAListOfMiddlewares()
    {
        try {
            $middlewares = new Middlewares([
                $this->mockMiddleware()
            ]);
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
            return;
        }

        $this->assertInstanceOf(PoolInterface::class, $middlewares);
    }

    /** @test */
    public function itShouldAlwaysGetFirstMiddleware()
    {

        $middlewares = new Middlewares([
            $a = $this->mockMiddleware(),
            $b = $this->mockMiddleware(),
            $c = $this->mockMiddleware()
        ]);

        $this->assertSame($a, $middlewares->head());
        $this->assertSame($a, $middlewares->head());
    }

    /** @test */
    public function itShouldReturnTailOfOriginalCollection()
    {
        $middlewares = new Middlewares([
            $a = $this->mockMiddleware(),
            $b = $this->mockMiddleware(),
            $c = $this->mockMiddleware()
        ]);

        $this->assertSame($a, $middlewares->head());
        /** @var PoolInterface $middlewares */
        $middlewares = $middlewares->tail();
        $this->assertSame($b, $middlewares->head());
        $middlewares = $middlewares->tail();
        $this->assertSame($c, $middlewares->head());
    }

    /**
     * @return MiddlewareInterface
     */
    private function mockMiddleware() : MiddlewareInterface
    {
        return $this->getMockForAbstractClass(MiddlewareInterface::class);
    }
}
