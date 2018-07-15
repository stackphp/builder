<?php

namespace Stack;

use Stack\Builder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Contracts\Service\ResetInterface;

class KernelStack implements HttpKernelInterface, TerminableInterface, ResetInterface
{

	protected $app;
	
	protected $builder;

	private $handler;
	
	public function __construct(HttpKernelInterface $app)
	{
		$this->app = $app;
		$this->builder = new Builder();
	}

	public function push(...$kernel): self
	{
		$this->builder->push(...$kernel);
		return $this;
	}

	public function unshift(...$kernel): self
	{
		$this->builder->unshift(...$kernel);
		return $this;
	}

	/**
	 * Handles a Request to convert it to a Response.
	 *
	 * When $catch is true, the implementation must catch all exceptions
	 * and do its best to convert them to a Response instance.
	 *
	 * @param Request $request A Request instance
	 * @param int $type The type of the request
	 *                         (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
	 * @param bool $catch Whether to catch exceptions or not
	 *
	 * @return Response A Response instance
	 *
	 * @throws \Exception When an Exception occurs during processing
	 */
	public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
	{
		if($this->handler === null) {
			$this->handler = $this->builder->resolve($this->app);
		}
		return $this->handler->handle($request,$type,$catch);
	}

	/**
	 * Terminates a request/response cycle.
	 *
	 * Should be called after sending the response and before shutting down the kernel.
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function terminate(Request $request, Response $response)
	{
		if($this->handler instanceof TerminableInterface) {
			$this->handler->terminate($request, $response);
		}
		$this->reset();
	}

	/**
	 * Reset the kernel to its initial status.
	 */
	public function reset()
	{
		$this->builder = new Builder();
		$this->handler = null;
		if($this->app instanceof ResetInterface) {
			$this->app->reset();
		}
	}
}
