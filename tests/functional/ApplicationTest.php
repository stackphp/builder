<?php

namespace functional;

use Application;
use PHPUnit\Framework\TestCase;
use Stack\Builder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApplicationTest extends TestCase
{
    public function testWithAppendMiddlewares()
    {        
        $request = Request::create('/foo');
        $app = new Application();
        $finished = false;

        $app->finish(function () use (&$finished) {
            $finished = true;
        });

        $stack = new Builder();
        $stack
            ->push('functional\Append', '.A')
            ->push('functional\Append', '.B');

        $app = $stack->resolve($app);
        $response = $app->handle($request);
        $app->terminate($request, $response);

        $this->assertSame('bar.B.A', $response->getContent());
        $this->assertTrue($finished);
    }
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
