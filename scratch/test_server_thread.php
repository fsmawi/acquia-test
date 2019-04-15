<?php

$app = require_once __DIR__ . '/../app/app.php';
require 'TestWip.php';

\Acquia\Wip\WipFactory::setConfigPath(__DIR__ . '/test.cfg');
// @todo Write the local pubkey and delete at the end.

// Set up a server.
$server = new \Acquia\Wip\Runtime\Server('localhost');
$server_storage = \Acquia\Wip\WipFactory::getObject('acquia.wip.storage.server');
$server->setTotalThreads(5);
$server_storage->save($server);

// Add work to the pool.
$wip = new TestWip();
$pool = new \Acquia\Wip\Runtime\WipPool();
$task = $pool->addTask($wip);

$pool = new \Acquia\Wip\Runtime\ThreadPool();
$pool->process();


echo "DONE\n";
