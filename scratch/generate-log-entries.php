<?php

if (isset($argv[1])) {
  $count = $argv[1];
  if (!is_numeric($count)) {
    die('The first argument must be the number of log entries to generate.');
  }
} else {
  $count = 25;
}

$app = require_once __DIR__ . '/../app/app.php';

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogLevel;

$store = new WipLogStore($app);
$wip_log = new WipLog($store);

for ($i = 0; $i <= $count; $i++) {
  $level = array_rand(WipLogLevel::getAll());
  $wip_log->log($level, 'message', rand(1, 5));
}
