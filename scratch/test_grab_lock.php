<?php

$app = require_once __DIR__ . '/../app/app.php';

\Acquia\Wip\WipFactory::setConfigPath(__DIR__ . '/test.cfg');

$lock = new \Acquia\WipIntegrations\DoctrineORM\MySqlLock();

$res = $lock->acquire('lock1', 3);
var_dump($res);
$res = $lock->acquire('lock2', 3);
var_dump($res);

$res = $lock->acquire('lock3', 3);
var_dump($res);
