# Stack/Stack

Stack of middlewares based on HttpKernelInterface.

Stack/Stack is a small library that helps you construct a nested
HttpKernelInterface decorator tree. It models it as a stack of middlewares.

## Example

If you want to decorate a [silex](https://github.com/fabpot/Silex) app with
logger and cache middlewares, you'll have to do something like this:

    use Symfony\Component\HttpKernel\HttpCache\Store;

    $app = new Silex\Application();

    $app->get('/', function () {
        return 'Hello World!';
    });

    $app = new Stack\Logger(
        new Symfony\Component\HttpKernel\HttpCache\HttpCache(
            $app,
            new Store(__DIR__.'/cache')
        ),
        new Monolog\Logger('app')
    );

This can get quite annoying indeed. Stack/Stack simplifies that:

    $stack = (new Stack\Stack())
        ->push('Stack\Logger', new Monolog\Logger('app'))
        ->push('Symfony\Component\HttpKernel\HttpCache\HttpCache', new Store(__DIR__.'/cache'));

    $app = $stack->resolve($app);

As you can see, by arranging the layers as a stack, they become a lot easier
to work with.

## Inspiration

* [Rack::Builder](http://rack.rubyforge.org/doc/Rack/Builder.html)
* [HttpKernel middlewares](https://igor.io/2013/02/02/http-kernel-middlewares.html)
