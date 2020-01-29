<?php
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class Application implements HttpKernelInterface
{
    private $kernel;

    private $dispatcher;

    public function __construct()
    {
        $routes = new RouteCollection();
        $routes->add('hello', new Route('/foo', [
            '_controller' => function (Request $request) {
                return new Response('bar');
            }]
        ));

        $matcher = new UrlMatcher($routes, new RequestContext());

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber(new RouterListener($matcher, new RequestStack()));
        
        $controllerResolver = new ControllerResolver();
        $argumentResolver = new ArgumentResolver();

        $this->kernel = new HttpKernel($this->dispatcher, $controllerResolver, new RequestStack(), $argumentResolver);
    }

    public function handle(Request $request, int $type = HttpKernelInterface::MASTER_REQUEST, bool $catch = true)
    {
        return $this->kernel->handle($request);
    }

    public function terminate(Request $request, Response $response)
    {
        $this->kernel->terminate($request, $response);
    }

    public function finish(callable $callback)
    {
        $dispatcher = $this->dispatcher;
        $app = $this;

        $request = Request::createFromGlobals();
        $response = $app->handle($request);

        $app->terminate($request, $response);

        $event = new TerminateEvent($app, $request, $response);
        $dispatcher->addSubscriber(new KernelTerminatedSubscriber($callback));
        $dispatcher->dispatch($event, KernelEvents::TERMINATE);
    }
}

class KernelTerminatedSubscriber implements EventSubscriberInterface
{
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminated'
        ];
    }

    public function onKernelTerminated(TerminateEvent $event)
    {
        call_user_func($this->callback);
    }
}
