<?php

namespace Stack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Stack\KernelStack;
use PHPUnit_Framework_TestCase as TestCase;

/** @covers Stack\KernelStack */
class KernelStackTest extends TestCase
{
    /** @test */
    public function withoutMiddlewaresItShouldReturnOriginalResponse()
    {
        $app = $this->getHttpKernelMock($response = new Response('ok'));
        
        $kernel = new KernelStack($app);
        $request = Request::create('/');
        $response = $kernel->handle($request);
        
        $this->assertSame('ok', $response->getContent());
    }
    
    /** @test */
    public function appendMiddlewareShouldAppendToBody()
    {
        $app = $this->getHttpKernelMock(new Response('ok'));
        
        $kernel = new KernelStack($app);
        $kernel->push('Stack\AppendA');
        $request = Request::create('/');
        $response = $kernel->handle($request);
        
        $this->assertSame('ok.A', $response->getContent());
    }
    
    /** @test */
    public function pushShouldReturnSelf()
    {
        $app = $this->getHttpKernelMock(new Response(''));
        $kernel = new KernelStack($app);
        $this->assertSame($kernel, $kernel->push('Stack\AppendA'));
    }
    
    /** @test */
    public function unshiftShouldReturnSelf()
    {
        $app = $this->getHttpKernelMock(new Response(''));
        $kernel = new KernelStack($app);
        $this->assertSame($kernel, $kernel->unshift('Stack\AppendA'));
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
}

class_exists(BuilderTest::class);
