<?php

$app = require_once __DIR__ . '/../app/app.php';

\Acquia\Wip\WipFactory::setConfigPath(__DIR__ . '/test.cfg');

$lock = new \Acquia\WipIntegrations\DoctrineORM\MySqlLock();

$res = $lock->acquire('lock1');
var_dump($res);
$res = $lock->acquire('lock2');
var_dump($res);

// Hold the lock.
sleep(300);

$res = $lock->release('lock1');
var_dump($res);
$res = $lock->release('lock2');
var_dump($res);
