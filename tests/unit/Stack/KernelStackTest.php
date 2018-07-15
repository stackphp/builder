<?php

namespace Stack;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Stack\KernelStack;

/** @covers Stack\KernelStack */
class KernelStackTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function withoutMiddlewaresItShouldReturnOriginalResponse()
    {
    }
}
