<?php

use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\UriCallback;

// Assumes this script is executed from the docroot.
$app = require_once __DIR__ . '/../app/app.php';

$url = $argv[1];
$callback = new UriCallback($url);
$signal = new Signal();
$callback->send($signal);
