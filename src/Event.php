<?php

namespace Jasny;

use Jasny\EventInterface;
use Jasny\Event\EventLoop;

/**
 * A non-blocking event.
 */
abstract class Event implements EventInterface
{
    /**
     * @var callable
     */
    private $callback;
    
    /**
     * Run the event.
     */
    abstract protected function run();
    
    /**
     * @param callable $callback
     */
    public function __construct(...$args)
    {
        $this->callback = array_pop($args);
        
        if (!is_callable($this->callback)) {
            throw new \InvalidArgumentException("Expected last argument to be a callable");
        }

        $this->addToLoop();
        
        $this->run(...$args);
    }
    
    /**
     * Add this event to the current event loop.
     * 
     * @throws \RuntimeException
     */
    protected function addToLoop()
    {
        $loop = EventLoop::current();
        
        if (!$loop) {
            throw new \LogicException("Unable to create an event outside an event loop");
        }
        
        $loop->addEvent($this);
    }
    
    /**
     * Called once, when done
     */
    public final function finish()
    {
        $this->trigger($this->callback);
    }
    
    /**
     * Trigger the callback
     */
    public function trigger($callback)
    {
        $callback();
    }
}
