<?php

$app = require_once 'app/app.php';
$entity_manager = $app['orm.em'];

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entity_manager);
