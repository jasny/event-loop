<?php

namespace Jasny\Event;

use Jasny\EventInterface;

/**
 * The event loop manager
 */
class EventLoop
{
    /**
     * Running event loops. Typically just one, but may be nested.
     * @var self[]
     */
    static protected $loops = [];
    
    /**
     * Running events
     * @var EventInterface[] 
     */
    protected $running = [];
    
    /**
     * Completed events
     * @var EventInterface[] 
     */
    protected $done = [];
    
    
    /**
     * @param callable $main
     */
    public function __construct(callable $main)
    {
        array_unshift(static::$loops, $this);
        
        $main();
        $this->loop();
    }
    
    /**
     * The event loop
     */
    protected function loop()
    {
        while(!empty($this->running) || !empty($this->done)) {
            $this->tick();
            $this->finish();
        }
        
        $this->end();
    }
    
    /**
     * Perform a tick for each running events
     */
    protected function tick()
    {
        foreach ($this->running as $i => $event) {
            $done = $event->tick();
            
            if ($done) {
                unset($this->running[$i]);
                $this->done[] = $event;
            }
        }
    }
    
    /**
     * Finish an event that is done
     */
    protected function finish()
    {
        $event = array_shift($this->done);
        
        if ($event) {
            $event->finish();
        }
    }
    
    /**
     * The loop is all done, let's clean up.
     */
    protected function end()
    {
        array_shift(static::$loops);
    }
    
    
    /**
     * Get the current event loop
     * 
     * @return self
     */
    public static function current()
    {
        return static::$loops[0] ?? null;
    }
    
    
    /**
     * Add an event to the loop
     * 
     * @param EventInterface $event
     */
    public function addEvent(EventInterface $event)
    {
        $this->running[] = $event;
    }
}
