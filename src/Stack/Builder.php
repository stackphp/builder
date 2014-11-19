<?php

namespace Stack;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

class Builder
{
    private $specs;

    public function __construct()
    {
        $this->specs = new \SplStack();
    }

    public function unshift(/*$kernelClass, $args...*/)
    {
        if (func_num_args() === 0) {
            throw new \InvalidArgumentException("Missing argument(s) when calling unshift");
        }

        $spec = func_get_args();
        $this->specs->unshift($spec);

        return $this;
    }

    public function push(/*$kernelClass, $args...*/)
    {
        if (func_num_args() === 0) {
            throw new \InvalidArgumentException("Missing argument(s) when calling push");
        }

        $spec = func_get_args();
        $this->specs->push($spec);

        return $this;
    }

    public function resolve(HttpKernelInterface $app)
    {
        foreach ($this->specs as $spec) {
            $prev = $app;

            $args = $spec;
            $firstArg = array_shift($args);

            if (is_callable($firstArg)) {
                $app = $firstArg($app);
            } else {
                $kernelClass = $firstArg;
                array_unshift($args, $app);

                $reflection = new \ReflectionClass($kernelClass);
                $app = $reflection->newInstanceArgs($args);
            }

            if (!$app instanceof TerminableInterface && $prev instanceof TerminableInterface) {
                $app = new StackedHttpKernel($app, $prev);
            }
        }

        return $app;
    }
}
