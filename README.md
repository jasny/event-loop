Jasny Event
===

[![Build Status](https://travis-ci.org/jasny/event.svg?branch=master)](https://travis-ci.org/jasny/event)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/event/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/event/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/event/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/event/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a1a1745c-1272-46a3-9567-7bbb52acda5a/mini.png)](https://insight.sensiolabs.com/projects/a1a1745c-1272-46a3-9567-7bbb52acda5a)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/event.svg)](https://packagist.org/packages/jasny/event)
[![Packagist License](https://img.shields.io/packagist/l/jasny/event.svg)](https://packagist.org/packages/jasny/event)

A simple event loop implementation. Aim is to make it easier to work with non-blocking streams, async queries, etc in PHP.

Installation
---

    composer require jasny/event

Usage
---

Create an event object that implements `EventInterface`. On `tick()` it can perform some action and then should return
if it's done or not. If the event is done, the `trigger()` method is called, which should invoke the callback.

```php
use Jasny\Event;

class ReadStreamEvent extends Event
{
    protected $fd;
    protected $data;

    protected function run($fd)
    {
        stream_set_blocking($fd, false);
        $this->fd = $fd;
    }

    public function tick()
    {
        $data = fread($this->fd, 1024);
        $this->data .= $data;

        return $data === EOF;
    }

    public function trigger($callback)
    {
        $callback($this->data);
    }
}
```

Create a new event loop, passing the a main function which may create events.

```php
use Jasny\Event\EventLoop;

new EventLoop(function() {
    $fd = fopen('path/to/some-large-file.txt');
    new ReadStreamEvent($fd, function(result) { echo $result; });
});
```

Naturally, you can create new events in an event callback. Events are automatically registered to the current event
loop.

You can add an event loop in any part of your application. Code after the event loop will run when all events are
finished. You can even nest event loops.
