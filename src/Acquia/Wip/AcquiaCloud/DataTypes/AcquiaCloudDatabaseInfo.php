<?php

namespace Acquia\Wip\AcquiaCloud\DataTypes;

use Acquia\Cloud\Api\Response\Database;
use Acquia\Wip\AcquiaCloud\AcquiaCloudDataType;

/**
 * Provides access to database details for a particular environment.
 */
class AcquiaCloudDatabaseInfo extends AcquiaCloudDataType implements \JsonSerializable {

  /**
   * The database role name.
   *
   * This is the same for all environments.
   *
   * @var string
   */
  private $roleName;

  /**
   * The database name.
   *
   * This is unique for each environment.
   *
   * @var string
   */
  private $instanceName;

  /**
   * The database user.
   *
   * @var string
   */
  private $user;

  /**
   * The password for the database.
   *
   * @var string
   */
  private $password;

  /**
   * The host name the database is on.
   *
   * Note: This is not the fully qualified hostname.
   *
   * @var string
   */
  private $hostName;

  /**
   * The database cluster ID.
   *
   * @var int
   */
  private $cluster;

  /**
   * Creates a new instance from the specified database information.
   *
   * @param Database $database
   *   The database info.
   */
  public function __construct(Database $database) {
    $this->setRoleName($database->name());
    $this->setInstanceName($database->instanceName());
    $this->setUsername($database->username());
    $this->setPassword($database->password());
    $this->setHost($database->host());
    $this->setDbCluster(intval($database->dbCluster()));
  }

  /**
   * Sets the database role name.
   *
   * @param string $role_name
   *   The database role name.
   */
  private function setRoleName($role_name) {
    if (!is_string($role_name) || empty($role_name)) {
      throw new \InvalidArgumentException('The name parameter must be a non-empty string.');
    }
    $this->roleName = $role_name;
  }

  /**
   * Gets the database role name.
   *
   * @return string
   *   The database role name.
   */
  public function getRoleName() {
    return $this->roleName;
  }

  /**
   * Sets the database instance name.
   *
   * @param string $name
   *   The database instance name.
   */
  private function setInstanceName($name) {
    if (!is_string($name) || empty($name)) {
      throw new \InvalidArgumentException('The name parameter must be a non-empty string.');
    }
    $this->instanceName = $name;
  }

  /**
   * Gets the database instance name.
   *
   * @return string
   *   The database instance name.
   */
  public function getInstanceName() {
    return $this->instanceName;
  }

  /**
   * Sets the database user name.
   *
   * @param string $user_name
   *   The database user name.
   */
  private function setUsername($user_name) {
    if (!is_string($user_name) || empty($user_name)) {
      throw new \InvalidArgumentException('The user_name parameter must be a non-empty string.');
    }
    $this->user = $user_name;
  }

  /**
   * Gets the database user name.
   *
   * @return string
   *   The database user name.
   */
  public function getUsername() {
    return $this->user;
  }

  /**
   * Sets the database password.
   *
   * @param string $password
   *   The database password.
   */
  private function setPassword($password) {
    if (!is_string($password) || empty($password)) {
      throw new \InvalidArgumentException('The password parameter must be a non-empty string.');
    }
    $this->password = $password;
  }

  /**
   * Gets the database password.
   *
   * @return string
   *   The database password.
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * Sets the host where the database resides.
   *
   * @param string $host
   *   The hostname.
   */
  private function setHost($host) {
    if (!is_string($host) || empty($host)) {
      throw new \InvalidArgumentException('The host parameter must be a non-empty string.');
    }
    $this->hostName = $host;
  }

  /**
   * Gets the host where the database resides.
   *
   * @return string
   *   The host name.
   */
  public function getHostName() {
    return $this->hostName;
  }

  /**
   * Sets the database cluster ID.
   *
   * @param int $cluster
   *   The cluster ID.
   */
  private function setDbCluster($cluster) {
    if (!is_int($cluster) || $cluster <= 0) {
      throw new \InvalidArgumentException('The cluster parameter must be a positive integer.');
    }
    $this->cluster = $cluster;
  }

  /**
   * Gets the database cluster ID.
   *
   * @return int
   *   The database cluster ID.
   */
  public function getCluster() {
    return $this->cluster;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $result = array(
      'name' => $this->roleName,
      'instance_name' => $this->instanceName,
      'user' => $this->user,
      'password' => $this->password,
      'host_name' => $this->hostName,
      'cluster' => $this->cluster,
    );
    return (object) $result;
  }

}
