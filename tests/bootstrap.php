<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

Phar::loadPhar(__DIR__ . '/../vendor/phpstan/phpstan/phpstan.phar', 'phpstan.phar');

require_once('phar://phpstan.phar/preload.php');
