<?php

use PHPUnit_Framework_TestCase as TestCase;
use Jasny\TestHelper;

use Jasny\Event\EventLoop;
use Jasny\EventInterface;

/**
 * @covers Jasny\Event\EventLoop
 */
class EventLoopTest extends TestCase
{
    use TestHelper;
    
    public function testRunMain()
    {
        $main = $this->createCallbackMock($this->once());
        new EventLoop($main);
    }
    
    public function testCurrent()
    {
        $inside = null;
        
        $loop = new EventLoop(function () use (&$inside) {
            $inside = EventLoop::current();
            $this->assertNotNull($inside);
        });
        
        $this->assertSame($loop, $inside);
        $this->assertNull(EventLoop::current());
    }
    
    /**
     * @depends testCurrent
     */
    public function testCurrentNestedLoop()
    {
        $inside1 = null;
        
        $loop1 = new EventLoop(function () use (&$inside1) {
            $inside1 = EventLoop::current();
            $inside2 = null;
            
            $loop2 = new EventLoop(function () use (&$inside2) {
                $inside2 = EventLoop::current();
            });
        
            $this->assertSame($loop2, $inside2);
            $this->assertSame($inside1, EventLoop::current());
        });
        
        $this->assertSame($loop1, $inside1);
        $this->assertNull(EventLoop::current());
    }
    
    public function testLoop()
    {
        $event = $this->createMock(EventInterface::class);
        $event->expects($this->exactly(3))->method('tick')->id('tick')
            ->willReturnOnConsecutiveCalls(false, false, true);
        $event->expects($this->once())->method('finish')->after('tick');
        
        new EventLoop(function() use ($event) {
            EventLoop::current()->addEvent($event);
        }, ['duration' => 0]);
    }
    
    public function testLoopMultipleEvents()
    {
        $events = [];
        $tickTimes = [3, 8, 1, 3, 4];
        $order = [];
        
        foreach ($tickTimes as $pos => $ticks) {
            $return = array_merge(array_fill(0, $ticks - 1, false), [true]);
            
            $event = $this->createMock(EventInterface::class);
            $event->expects($this->exactly($ticks))->method('tick')->willReturnOnConsecutiveCalls(...$return);
            $event->expects($this->once())->method('finish')->willReturnCallback(function() use (&$order, $pos) {
                $order[] = $pos;
            });
            
            $events[] = $event;
        }
        
        new EventLoop(function() use ($events) {
            foreach ($events as $event) {
                EventLoop::current()->addEvent($event);
            }
        }, ['duration' => 0]);
        
        $this->assertSame([2, 0, 3, 4, 1], $order);
    }
    
    public function testSleepUntil()
    {
        $event = $this->createMock(EventInterface::class);
        $event->expects($this->exactly(3))->method('tick')->id('tick')
            ->willReturnOnConsecutiveCalls(false, false, true);
        $event->expects($this->once())->method('finish')->after('tick');
        
        $startime = microtime(true);
        
        new EventLoop(function() use ($event) {
            EventLoop::current()->addEvent($event);
        }, ['duration' => 150000]);
        
        $this->assertGreaterThan(300000, (int)((microtime(true) - $startime) * 1000000));
    }
}
