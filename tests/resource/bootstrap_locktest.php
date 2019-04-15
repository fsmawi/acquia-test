<?php

$app = require_once __DIR__ . '/../../app/app.php';

$lock = new Acquia\WipService\Test\LockTester();
$lock->setAcquireTimeout(5);

$lock->setPrefix($_SERVER['argv'][3]);
$lock->setTempFile($_SERVER['argv'][2]);
$lock->sleep($_SERVER['argv'][1]);
