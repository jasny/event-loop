<?php

namespace Jasny;

/**
 * Inteface for a non-blocking event
 */
interface EventInterface
{
    /**
     * Called on each tick of the event loop
     */
    public function tick();
    
    /**
     * Called once, when done
     */
    public function finish();
}
