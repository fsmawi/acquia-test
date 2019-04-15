<?php

namespace Acquia\Wip\Drupal;

use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\WipLogInterface;

/**
 * Provides an easy way to create Ssh instances for drush commands.
 */
class DrushCommands {

  /**
   * The Environment associated with the resulting drush command.
   *
   * @var EnvironmentInterface
   */
  private $environment = NULL;

  /**
   * The WipLog instance.
   *
   * @var WipLogInterface
   */
  private $logger = NULL;

  /**
   * The Wip ID associated with the Drush command.
   *
   * @var int
   */
  private $id = NULL;

  /**
   * Creates a new instance of DrushCommands for creating Drush commands.
   *
   * @param EnvironmentInterface $environment
   *   The Environment instance that provides the hosting sitegroup,
   *   environment, and server list.
   * @param WipLogInterface $logger
   *   The logger to use.
   * @param int $id
   *   The Wip ID to log against.
   */
  public function __construct(EnvironmentInterface $environment, WipLogInterface $logger, $id) {
    $this->environment = $environment;
    $this->logger = $logger;
    $this->id = $id;
  }

  /**
   * Gets the EnvironmentInterface instance associated with this object.
   *
   * @return EnvironmentInterface
   *   The environment.
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * Returns the WipLogInterface instance.
   *
   * @return WipLogInterface
   *   The logger;
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * The ID of the Wip object to log against.
   *
   * @return int
   *   The Wip ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Creates a DrushSsh instance that will clear the Drupal cache.
   *
   * @param string $scope
   *   Optional. The scope of the cache clear. If not provided, all caches will
   *   be cleared.
   *
   * @return DrushSsh
   *   The DrushSsh command.
   */
  public function getCacheClear($scope = 'all') {
    $command = sprintf('cache-clear %s', $scope);
    if ($scope === 'all') {
      $description = 'Clear all Drupal caches.';
    } else {
      $description = sprintf('Clear the Drupal %s cache.', $scope);
    }
    $drush_ssh = new DrushSsh();
    $result = $drush_ssh->initialize(
      $this->getEnvironment(),
      $description,
      $this->getLogger(),
      $this->getId()
    );
    $result->setCommand($command);
    return $result;
  }

}
