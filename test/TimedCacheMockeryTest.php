<?php

class TimedCacheMockeryTest extends PHPUnit_Framework_TestCase
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
    
        $this->mockLoader = \Mockery::mock('ObjectLoader');
        $this->mockClock = \Mockery::mock('Clock');
        $this->mockReloadPolicy = \Mockery::mock('ReloadPolicy');
        
        // PHPではクラスのインスタンスは参照渡し
        $this->cache = new TimedCache($this->mockLoader, $this->mockClock, $this->mockReloadPolicy);
    }
    
    // 3-1: 
    public function testLoadsObjectThatIsNotCached()
    {
        // モックを作る
        $this->mockLoader->shouldReceive('load')->with('KEY')->andReturn('VALUE');
        $this->mockLoader->shouldReceive('load')->with('KEY2')->andReturn('VALUE2');
        // 原著では書いてないが、完成形のTimedCacheインスタンスを使おうとするとどうしてもgetCurrentTime()が呼ばれる
        $this->mockClock->shouldReceive('getCurrentTime')
            ->globally()->ordered()
            ->atLeast()->once()
            ->andReturn($this->loadTime, $this->fetchTime);
        
        // テストをモックのコンテナに対して書く
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
        $this->assertSame('VALUE2', $this->cache->lookup('KEY2'));
    }
    
    // 3-2: 
    public function testCachedObjectsAreNotReloaded()
    {
        // ObjectLoaderが2回以上呼び出されるとエラーが出るように、once()を読んで呼び出し可能回数を設定
        $this->mockLoader->shouldReceive('load')->with('KEY')->once()->andReturn('VALUE');
        // 原著では書いてないが、完成形のTimedCacheインスタンスを使おうとするとmockClock, mockReloadPolicyが必要
        $this->mockClock->shouldReceive('getCurrentTime')
            ->globally()->ordered()
            ->atLeast()->once()
            ->andReturn($this->loadTime, $this->fetchTime);
        $this->mockReloadPolicy->shouldReceive('shouldReload')
            ->with($this->loadTime, $this->fetchTime)
            ->andReturn(false)
            ->atLeast()->once();
        
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
    }
    
    // 3-3, 3-4:
    // ※但しPHPUnitでは順序を既定するメソッドが無さそう(Mockeryのordered)
    public function testReturnsCachedObjectWithinTimeout()
    {
        $this->mockLoader->shouldReceive('load')
            ->globally()->ordered()
            ->with('KEY')->once()->andReturn('VALUE');
        
        // これでテストは通る。これを $mockLoaderよりも前に定義するとNGケースを作れる。
        // $mockLoaderと$mockClockを生成するコードの順序に気を付ける。。。これは面倒。。。（orderedの引数に数字を与えても、グループナンバーとしてしか処理されないぽい）
        $this->mockClock->shouldReceive('getCurrentTime')
            ->globally()->ordered()
            ->atLeast()->once()
            ->andReturn($this->loadTime, $this->fetchTime);
        
        $this->mockReloadPolicy->shouldReceive('shouldReload')
            ->with($this->loadTime, $this->fetchTime)
            ->andReturn(false)
            ->atLeast()->once();
        
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
    }
    
    // 3-5:
    public function testReloadsCachedObjectAfterTimeout()
    {
        $this->mockLoader->shouldReceive('load')
            ->with('KEY')->twice()->andReturn('VALUE', 'NEW_VALUE');
        
        $this->mockClock->shouldReceive('getCurrentTime')
            ->times(3)
            ->andReturn($this->loadTime, $this->fetchTime, $this->reloadTime);
        
        $this->mockReloadPolicy->shouldReceive('shouldReload')
            ->with($this->loadTime, $this->fetchTime)
            ->andReturn(true)
            ->atLeast()->once();
        
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
        $this->assertSame('NEW_VALUE', $this->cache->lookup('KEY'));
    }
    
}
