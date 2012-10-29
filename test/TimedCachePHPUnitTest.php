<?php

class TimedCachePHPUnitTest extends PHPUnit_Framework_TestCase
{
    private $loadTime;
    private $fetchTime;
    private $reloadTime;
    private $mockLoader;
    private $mockClock;
    private $mockReloadPolicy;
    
    public function setUp()
    {
        $this->loadTime = strtotime('2012/10/01 12:00:00');
        $this->fetchTime = strtotime('2012/10/01 12:00:00');
        $this->reloadTime = strtotime('2012/11/11 11:11:11');
    
        $this->mockLoader = $this->getMock('ObjectLoader', array('load'));
        $this->mockClock = $this->getMock('Clock', array('getCurrentTime'));
        $this->mockReloadPolicy = $this->getMock('ReloadPolicy', array('shouldReload'));
        
        // PHPではクラスのインスタンスは参照渡し
        $this->cache = new TimedCache($this->mockLoader, $this->mockClock, $this->mockReloadPolicy);
    }
    
    // 3-1: 
    public function testLoadsObjectThatIsNotCached()
    {
        $this->mockLoader->expects($this->any())
            ->method('load')
            ->will($this->returnValueMap(
                array( array('KEY', 'VALUE'), array('KEY2', 'VALUE2') )
            ));
        
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
        $this->assertSame('VALUE2', $this->cache->lookup('KEY2'));
    }
    
    // 3-2: 
    public function testCachedObjectsAreNotReloaded()
    {
        // ObjectLoaderが2回以上呼び出されるとエラーが出るように、expects()に$this->once()を渡して呼び出し可能回数を設定
        $this->mockLoader->expects($this->once())
            ->method('load')
            ->will($this->returnValueMap(
                array( array('KEY', 'VALUE') )
            ));
        
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
    }
    
    // 3-3, 3-4:
    // ※但しPHPUnitでは順序を既定するメソッドが無さそう(Mockeryのordered)
    public function testReturnsCachedObjectWithinTimeout()
    {
        // 1つ目の隣接オブジェクト（3-2の流用）
        $this->mockLoader->expects($this->once())
            ->method('load')
            ->will($this->returnValueMap(
                array( array('KEY', 'VALUE') )
            ));
            
        // 2つ目の隣接オブジェクト
        $this->mockClock->expects($this->atLeastOnce())
            ->method('getCurrentTime')
            ->will($this->returnValue($this->loadTime, $this->fetchTime));
        
        // 3つ目の隣接（ｒｙ
        $this->mockReloadPolicy->expects($this->atLeastOnce())
            ->method('shouldReload')
            ->will($this->returnValueMap(
                array( array($this->loadTime, $this->fetchTime, false) )
            ));
        
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
    }
    
    // 3-5:
    public function testReloadsCachedObjectAfterTimeout()
    {
        $this->mockLoader->expects($this->at(0))
            ->method('load')->with('KEY')
            ->will($this->returnValue('VALUE'));
        $this->mockLoader->expects($this->at(1))
            ->method('load')->with('KEY')
            ->will($this->returnValue('NEW_VALUE'));
            
        $this->mockClock->expects($this->exactly(3))
            ->method('getCurrentTime')
            ->will($this->returnValue($this->loadTime, $this->fetchTime, $this->reloadTime));
        
        $this->mockReloadPolicy->expects($this->atLeastOnce())
            ->method('shouldReload')
            ->will($this->returnValueMap(
                array( array($this->loadTime, $this->fetchTime, true) )
            ));
        
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
        $this->assertSame('NEW_VALUE', $this->cache->lookup('KEY'));

    }
    
}
