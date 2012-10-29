<?php

class TimedCache
{
    private $loader;
    private $clock;
    private $reloadPolicy;
    private $cachedValues = array();

    public function __construct($loader, $clock, $reloadPolicy)
    {
        $this->loader = $loader;
        $this->clock = $clock;
        $this->reloadPolicy = $reloadPolicy;
    }
    
    public function lookup($key)
    {
        // キャッシュが存在しないか、有効期限切れの場合にリロード
        if(!array_key_exists($key, $this->cachedValues)
            || $this->reloadPolicy->shouldReload($this->cachedValues[$key]->loadTime, $this->clock->getCurrentTime())){
            $this->cachedValues[$key] = new TimestampedValue(
                $this->loader->load($key),
                $this->clock->getCurrentTime()
            );
        }
        
        // 保存したキャッシュを読み出し
        return $this->cachedValues[$key]->value;
    }
}
