<?php

/**
 * Provides access to the migration related CLI commands.
 *
 * Usage:
 * ```
 * php app/migrations.php --configuration=config/migration-config.yml
 * ```
 *
 * Migrations can be used to act as an install / update hook. Every migration
 * will create a file in the dir specified by the above mentioned config file.
 *
 * After changing the schema classes just run:
 * ```
 * php app/migrations.php --configuration=config/migration-config.yml migrations:diff
 * ```
 *
 * To generate a migration that handles the necessary schema changes. Edit as
 * necessary - the up method holds the forward update path, the down holds the
 * fallback update path.
 *
 * One can create a skeleton migration file with:
 * ```
 * php app/migrations.php --configuration=config/migration-config.yml migrations:generate
 * ```
 *
 * To install or update the local database with migrations run:
 * ```
 * php app/migrations.php --configuration=config/migration-config.yml migrations:migrate
 * ```
 *
 * One can also execute just one migration file by specifying its version.
 *
 * Doctrine ORM Migrations holds the applied migrations in a table defined in
 * the above mentioned config file to track what has been already applied.
 */

$app = require_once 'app/app.php';
$entity_manager = $app['orm.em'];

// Migration files are created based on the current date and time. To fix the
// differences between timezone let's use UTC as a common timezone.
date_default_timezone_set('UTC');

// All the commands of the Doctrine Console require access to the EntityManagerInterface
// or DBAL Connection. You have to inject them into the console application
// using so called Helper-Sets. This requires either the db or the em helpers to
// be defined in order to work correctly.
// http://doctrine-orm.readthedocs.org/en/latest/reference/tools.html#configuration
$helper_set = new \Symfony\Component\Console\Helper\HelperSet(array(
  'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($entity_manager->getConnection()),
  'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($entity_manager),
  'dialog' => new \Symfony\Component\Console\Helper\DialogHelper(),
));

// To get migration commands work via CLI, the documentation suggests to modify
// the doctrine.php. To avoid hacking the provided the files, this application
// can act as a solution.
// http://doctrine-migrations.readthedocs.org/en/latest/reference/introduction.html#register-console-commands
$commands = array(
  new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
  new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
  new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
  new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
  new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
  new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand(),
);

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helper_set, $commands);
