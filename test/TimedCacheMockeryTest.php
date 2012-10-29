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
        
        // PHP�ł̓N���X�̃C���X�^���X�͎Q�Ɠn��
        $this->cache = new TimedCache($this->mockLoader, $this->mockClock, $this->mockReloadPolicy);
    }
    
    // 3-1: 
    public function testLoadsObjectThatIsNotCached()
    {
        // ���b�N�����
        $this->mockLoader->shouldReceive('load')->with('KEY')->andReturn('VALUE');
        $this->mockLoader->shouldReceive('load')->with('KEY2')->andReturn('VALUE2');
        // �����ł͏����ĂȂ����A�����`��TimedCache�C���X�^���X���g�����Ƃ���Ƃǂ����Ă�getCurrentTime()���Ă΂��
        $this->mockClock->shouldReceive('getCurrentTime')
            ->globally()->ordered()
            ->atLeast()->once()
            ->andReturn($this->loadTime, $this->fetchTime);
        
        // �e�X�g�����b�N�̃R���e�i�ɑ΂��ď���
        $this->assertSame('VALUE', $this->cache->lookup('KEY'));
        $this->assertSame('VALUE2', $this->cache->lookup('KEY2'));
    }
    
    // 3-2: 
    public function testCachedObjectsAreNotReloaded()
    {
        // ObjectLoader��2��ȏ�Ăяo�����ƃG���[���o��悤�ɁAonce()��ǂ�ŌĂяo���\�񐔂�ݒ�
        $this->mockLoader->shouldReceive('load')->with('KEY')->once()->andReturn('VALUE');
        // �����ł͏����ĂȂ����A�����`��TimedCache�C���X�^���X���g�����Ƃ����mockClock, mockReloadPolicy���K�v
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
    // ���A��PHPUnit�ł͏��������肷�郁�\�b�h����������(Mockery��ordered)
    public function testReturnsCachedObjectWithinTimeout()
    {
        $this->mockLoader->shouldReceive('load')
            ->globally()->ordered()
            ->with('KEY')->once()->andReturn('VALUE');
        
        // ����Ńe�X�g�͒ʂ�B����� $mockLoader�����O�ɒ�`�����NG�P�[�X������B
        // $mockLoader��$mockClock�𐶐�����R�[�h�̏����ɋC��t����B�B�B����͖ʓ|�B�B�B�iordered�̈����ɐ�����^���Ă��A�O���[�v�i���o�[�Ƃ��Ă�����������Ȃ��ۂ��j
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
