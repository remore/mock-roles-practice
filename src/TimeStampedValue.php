<?php

class TimestampedValue
{
    // PHP�ł�final�L�[���[�h�̓N���X�ƃ��\�b�h�݂̂ɓK�p�\�Ȃ̂ŏȗ�
    public $value; 
    public $loadTime;
    
    public function __construct($value, $loadTime)
    {
        $this->value = $value;
        $this->loadTime = $loadTime;
    }
}