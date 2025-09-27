<?php
// charge l'autoloader Composer
require dirname(__DIR__) . '/vendor/autoload.php';

// force l'env de test
$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';
