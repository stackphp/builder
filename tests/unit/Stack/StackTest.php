<?php

namespace Stack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/** @covers Stack\Stack */
class StackTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function withoutMiddlewaresItShouldReturnOriginalResponse()
    {
        $app = $this->getHttpKernelMock(new Response('ok'));

        $stack = new Stack();
        $resolved = $stack->resolve($app);

        $request = Request::create('/');
        $response = $resolved->handle($request);

        $this->assertInstanceOf('Stack\StackedHttpKernel', $resolved);
        $this->assertSame('ok', $response->getContent());
    }

    /** @test */
    public function resolvedKernelShouldDelegateTerminateCalls()
    {
        $app = $this->getTerminableMock();

        $stack = new Stack();
        $resolved = $stack->resolve($app);

        $request = Request::create('/');
        $response = new Response('ok');

        $resolved->handle($request);
        $resolved->terminate($request, $response);
    }

    /** @test */
    public function pushShouldReturnSelf()
    {
        $stack = new Stack();
        $this->assertSame($stack, $stack->push('Stack\AppendA'));
    }

    /** @test */
    public function appendMiddlewareShouldAppendToBody()
    {
        $app = $this->getHttpKernelMock(new Response('ok'));

        $stack = new Stack();
        $stack->push('Stack\AppendA');
        $resolved = $stack->resolve($app);

        $request = Request::create('/');
        $response = $resolved->handle($request);

        $this->assertSame('ok.A', $response->getContent());
    }

    /** @test */
    public function stackedMiddlewaresShouldWrapInReverseOrder()
    {
        $app = $this->getHttpKernelMock(new Response('ok'));

        $stack = new Stack();
        $stack->push('Stack\AppendA');
        $stack->push('Stack\AppendB');
        $resolved = $stack->resolve($app);

        $request = Request::create('/');
        $response = $resolved->handle($request);

        $this->assertSame('ok.B.A', $response->getContent());
    }

    /** @test */
    public function resolveShouldPassPushArgumentsToMiddlewareConstructor()
    {
        $app = $this->getHttpKernelMock(new Response('ok'));

        $stack = new Stack();
        $stack->push('Stack\Append', '.foo');
        $stack->push('Stack\Append', '.bar');
        $resolved = $stack->resolve($app);

        $request = Request::create('/');
        $response = $resolved->handle($request);

        $this->assertSame('ok.bar.foo', $response->getContent());
    }

    private function getHttpKernelMock(Response $response)
    {
        $app = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $app->expects($this->any())
            ->method('handle')
            ->with($this->isInstanceOf('Symfony\Component\HttpFoundation\Request'))
            ->will($this->returnValue($response));

        return $app;
    }

    private function getTerminableMock()
    {
        $app = $this->getMock('Stack\TerminableHttpKernel');
        $app->expects($this->once())
            ->method('terminate')
            ->with(
                $this->isInstanceOf('Symfony\Component\HttpFoundation\Request'),
                $this->isInstanceOf('Symfony\Component\HttpFoundation\Response')
            );

        return $app;
    }
}

abstract class TerminableHttpKernel implements HttpKernelInterface, TerminableInterface
{
}

class Append implements HttpKernelInterface
{
    private $app;
    private $appendix;

    public function __construct(HttpKernelInterface $app, $appendix)
    {
        $this->app = $app;
        $this->appendix = $appendix;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $response = clone $this->app->handle($request, $type, $catch);
        $response->setContent($response->getContent().$this->appendix);

        return $response;
    }
}

class AppendA extends Append
{
    public function __construct(HttpKernelInterface $app)
    {
        parent::__construct($app, '.A');
    }
}

class AppendB extends Append
{
    public function __construct(HttpKernelInterface $app)
    {
        parent::__construct($app, '.B');
    }
}
