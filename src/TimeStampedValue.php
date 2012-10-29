<?php

class TimestampedValue
{
    // PHPではfinalキーワードはクラスとメソッドのみに適用可能なので省略
    public $value; 
    public $loadTime;
    
    public function __construct($value, $loadTime)
    {
        $this->value = $value;
        $this->loadTime = $loadTime;
    }
}