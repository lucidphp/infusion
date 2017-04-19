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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;

/**
 * Class Delegate
 * @package Lucid\Infusion
 * @author Thomas Appel <mail@thomas-appel.com>
 */
final class Delegate implements DelegateInterface
{
    /** @var PoolInterface */
    private $middlewares;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /**
     * Delegate constructor.
     * @param PoolInterface $middlewares
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(PoolInterface $middlewares, ResponseFactoryInterface $responseFactory)
    {
        $this->middlewares = $middlewares;
        $this->responseFactory = $responseFactory;
    }

    /** {@inheritdoc} */
    public function process(ServerRequestInterface $request) : ResponseInterface
    {
        if (($middleware = $this->middlewares->head()) === null) {
            return $this->errorResponse();
        }

        return $middleware->process($request, new self($this->middlewares->tail(), $this->responseFactory));
    }

    /** {@inheritdoc} */
    private function errorResponse() : ResponseInterface
    {
        return $this->responseFactory
            ->createResponse()
            ->withStatus(444, 'no response.');
    }
}
