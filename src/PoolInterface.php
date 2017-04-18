<?php

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

interface PoolInterface
{
    public function head() : ?MiddlewareInterface;

    public function tail() : self;
}
