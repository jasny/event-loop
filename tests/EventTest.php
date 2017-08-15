<?php

use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Jasny\TestHelper;

use Jasny\Event;
use Jasny\Event\EventLoop;

/**
 * @covers Jasny\Event
 */
class EventLoopTest extends TestCase
{
    use TestHelper;
    
    /**
     * @var EventLoop|MockObject
     */
    protected $loop;
    
    public function setUp()
    {
        $this->loop = $this->createMock(EventLoop::class);
        
        $refl = new ReflectionProperty(EventLoop::class, 'loops');
        $refl->setAccessible(true);
        $refl->setValue(null, [$this->loop]);
    }
    
    protected function endLoop()
    {
        $refl = new ReflectionProperty(EventLoop::class, 'loops');
        $refl->setAccessible(true);
        $refl->setValue(null, []);
    }
    
    public function tearDown()
    {
        $this->endLoop();
    }
    
    
    public function testCreateEvent()
    {
        $event = $this->createPartialMock(Event::class, ['tick', 'run']);
        
        $this->loop->expects($this->once())->method('addEvent')->with($event);
        $event->expects($this->once())->method('run')->with('foo', 'bar');
        
        $event->__construct('foo', 'bar', function() {});
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateEventNotCallable()
    {
        $event = $this->createPartialMock(Event::class, ['tick', 'run']);
        $event->__construct([]);
    }
    
    /**
     * @expectedException LogicException
     */
    public function testCreateEventNoLoop()
    {
        $this->endLoop();
        
        $event = $this->createPartialMock(Event::class, ['tick', 'run']);
        $event->__construct(function() {});
    }
    
    public function testFinish()
    {
        $callback = $this->createCallbackMock($this->once());
        $event = $this->createPartialMock(Event::class, ['tick', 'run']);
        
        $event->__construct('foo', 'bar', $callback);
        
        $event->finish();
    }
}
