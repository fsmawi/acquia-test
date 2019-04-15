<?php

// Use the test database to avoid running the tests on the runtime database.
// This environment variable needs to be set before requiring app/app.php.
$override = getenv('WIP_SERVICE_OVERRIDE_DATABASE');
if ($override) {
  putenv('WIP_SERVICE_DATABASE=silex_test');
}

$app = require __DIR__ . '/../app/app.php';

// Automatically update the schema before running the tests.
$entity_manager = $app['orm.em'];
$schema_tool = new \Doctrine\ORM\Tools\SchemaTool($entity_manager);
$metadata = $entity_manager->getMetadataFactory()->getAllMetadata();
$schema_tool->updateSchema($metadata, TRUE);
