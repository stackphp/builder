<?php

namespace Stack;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StackedHttpKernel implements HttpKernelInterface, TerminableInterface
{
    private $app;
    private $prev;

    public function __construct(HttpKernelInterface $app, TerminableInterface $prev)
    {
        $this->app = $app;
        $this->prev = $prev;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        return $this->app->handle($request, $type, $catch);
    }

    public function terminate(Request $request, Response $response)
    {
        $app = $this->app instanceof TerminableInterface ? $this->app : $this->prev;
        $app->terminate($request, $response);
    }
}
