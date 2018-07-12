Simple Event loop
===

[![Build Status](https://travis-ci.org/jasny/event-loop.svg?branch=master)](https://travis-ci.org/jasny/event-loop)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/event/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/event/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/event/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/event/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a1a1745c-1272-46a3-9567-7bbb52acda5a/mini.png)](https://insight.sensiolabs.com/projects/a1a1745c-1272-46a3-9567-7bbb52acda5a)

**This library is not intended for production use, but to show PHP developers how an [event loop](https://en.wikipedia.org/wiki/Event_loop) works.**

If you're looking for a production ready event loop implementation in PHP, try [ReactPHP](https://reactphp.org/).

How it works
---

An event loop provides a way to achief asynchronous execution, without relying on the operating system for parallel processes and threads. This method is most favious for it's use in JavaScript.

Typically in PHP, your script needs to wait for actions like reading from disk, fetching from the database or doing an HTTP request with curl. With the event loop, you initiate this action but than continue to the next line of code. For this, you create an event which has a callback, named the handler, to process the result at a later time.

In PHP, this can be achieved using [non-blocking IO](http://php.net/manual/en/function.stream-set-blocking.php). Rather than waiting on the operation to be finished, the returned resource needs to be checked continously to see if the IO operation is done. This recourse is wrapped in the event object, together with the handler.

In the event flow, events will never be handled in between executing two random lines of code. Instead the current flow of execution is always allowed to finish. This does not end the program if there are any unresolved events.

Each time a flow of execution is finished, the event loop will check if events non-blocking IO to see if the operation has finished. If that's the case, the handler will be called with the result of the IO operation as argument.

When all events are resolved, the loop will exist and the program can end.

Usage
---

Create an event object that implements `EventInterface`. On `tick()` it can perform some action and then should return
if it's done or not. If the event is done, the `trigger()` method is called, which should invoke the callback.

_Note that `tick()` method is not related to PHP ticks._

```php
use Jasny\Event\Event;

/**
 * Event for asynchronous read of a file.
 */
class ReadFileEvent extends Event
{
    /**
     * @var resource  non-blocking IO
     */
    protected $fd;
    
    /**
     * @var string  read data
     */
    protected $data;

    /**
     * This method is called when the event is created with the intent to initiate the IO operation.
     *
     * @param string $filename
     */
    protected function run($filename = null)
    {
        if (!is_string($filename)) {
            throw new \InvalidArgumentException("Expected a filename");
        }
        
        $fd = fopen($filename, 'r');

        if (!is_resource($fd)) {
            throw new \RuntimeException("Failed to open file '$filename'");
        }
        
        stream_set_blocking($fd, false);
        $this->fd = $fd;
    }

    /**
     * Called each time between resolving events.
     *
     * @returns boolean  true indicates the operation is finished
     */
    public function tick()
    {
        $data = fread($this->fd, 10240);
        $this->data .= $data;

        return feof($data);
    }

    /**
     * Called when the operation is finished
     *
     * @param callable $callback
     */
    protected function trigger($callback)
    {
        $callback($this->data);
    }

    /**
     * Clean up after the event is handled.
     */
    protected function cleanup()
    {
        fclose($this->fd);
        $this->data = null;
    } 
}
```

Create a new event loop, passing the a main function which may create events.

```php
use Jasny\Event\EventLoop;

new EventLoop(function() {
    // Do something here
    
    new ReadFileEvent('path/to/file.txt', function($result) { echo $result; });
    
    // Continue doing things here
});
```

Naturally, you can create new events in an event callback. Events are automatically registered to the current event
loop.

You can add an event loop in any part of your application. Code after the event loop will run when all events are
finished. You can even nest event loops.

_Please take a look at the code, to get a deeper understanding. It's only 111 lines of code (without comments), so don't be scared._ 
