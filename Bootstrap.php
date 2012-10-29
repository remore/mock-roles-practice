<?php

// For composer
require_once 'Vendor/autoload.php';

// For your component
spl_autoload_register(function ($class) {
    if (file_exists('src/'.$class.'.php')){
        include 'src/' . $class . '.php';
    }
});

