<?php declare(strict_types=1);

/*
 * This File is part of the Lucid\Infusion package
 *
 * (c) iwyg <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Infusion;

use Interop\Http\ServerMiddleware\MiddlewareInterface;

/**
 * Class Middlewares
 * @package Lucid\Infusion
 * @author Thomas Appel <mail@thomas-appel.com>
 */
final class Middlewares implements PoolInterface
{
    /** @var MiddlewareInterface[] */
    private $middlewares;

    /**
     * Middlewares constructor.
     * @param array $middlewares
     */
    public function __construct(array $middlewares)
    {
        $this->setMiddlewares(...$middlewares);
    }

    /** {@inheritdoc} */
    public function head() : ?MiddlewareInterface
    {
        return $this->middlewares[0] ?? null;
    }


    /** {@inheritdoc} */
    public function tail() : PoolInterface
    {
        return new self(array_slice($this->middlewares, 1));
    }

    /**
     * @param MiddlewareInterface[] ...$middlewares
     */
    private function setMiddlewares(MiddlewareInterface ...$middlewares) : void
    {
        $this->middlewares = $middlewares;
    }
}
