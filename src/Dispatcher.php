<?php declare(strict_types=1);

/*
 * This File is part of the Lucid\Infusion package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

namespace Lucid\Infusion;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Middleware\StackInterface;
use Psr\Http\Middleware\DelegateInterface;
use Psr\Http\Middleware\MiddlewareInterface;
use Psr\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Middleware\ClientMiddlewareInterface;
use Interop\Http\Factory\ResponseFactoryInterface;

/**
 * Class Dispatcher
 * @package Lucid\Infusion
 * @author Thomas Appel <mail@thomas-appel.com>
 */
class Dispatcher implements StackInterface, DelegateInterface
{
    /** @var ClientMiddlewareInterface[]|ServerMiddlewareInterface[]  */
    private $middleware;

    /** @var int */
    private $index;

    /** @var ResponseFactoryInterface */
    private $factory;

    /**
     * Dispatcher constructor.
     *
     * @param ResponseFactoryInterface $factory
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(ResponseFactoryInterface $factory, array $middleware = [])
    {
        $this->factory = $factory;
        $this->set(...$middleware);
    }

    /**
     * {@inheritdoc}
     */
    public function withMiddleware(MiddlewareInterface $middleware) : StackInterface
    {
        $mw   = $this->middleware;
        $mw[] = $middleware;

        return new self($this->factory, $mw);
    }

    /**
     * {@inheritdoc}
     */
    public function withoutMiddleware(MiddlewareInterface $middleware) : StackInterface
    {
        return new self(
            $this->factory,
            array_filter($this->middleware, function (MiddlewareInterface $mw) use ($middleware) {
                return $mw !== $middleware;
            })
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(RequestInterface $request) : ResponseInterface
    {
        $response = $this->next($request);
        $this->reset();

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function next(RequestInterface $request) : ResponseInterface
    {
        if ($this->valid()) {
            return $this->middleware[$this->index]->process($request, $this->newDelegate());
        }

        return $this->newErrResponse();
    }

    /**
     * @return bool
     */
    private function valid() : bool
    {
        return $this->index > -1;
    }

    /** reset the index */
    private function reset() : void
    {
        $this->index = count($this->middleware) - 1;
    }

    /**
     * @return \Lucid\Infusion\Dispatcher
     */
    private function newDelegate() : self
    {
        $stack = clone $this;
        $stack->set(...array_slice($this->middleware, 0, $this->index));

        return $stack;
    }

    /**
     * @param \Psr\Http\Middleware\MiddlewareInterface[] ...$middleware
     */
    private function set(MiddlewareInterface ...$middleware) : void
    {
        $this->middleware = $middleware;
        $this->reset();
    }

    /**
     * @return ResponseInterface
     */
    private function newErrResponse() : ResponseInterface
    {
        return $this->factory->createResponse()->withStatus(444, 'no response.');
    }
}
