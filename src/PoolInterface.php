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
 * Interface PoolInterface
 * @package Lucid\Infusion
 */
interface PoolInterface
{
    /**
     * Must return the first Middleware of the pool or NULL;
     *
     * @return MiddlewareInterface|null
     */
    public function head() : ?MiddlewareInterface;

    /**
     * Must return a new instance containing the tail of the original pool.
     *
     * @return PoolInterface
     */
    public function tail() : self;
}
