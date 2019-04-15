<?php

namespace Acquia\Wip\AcquiaCloud\DataTypes;

use Acquia\Cloud\Api\Response\SvnUser;
use Acquia\Wip\AcquiaCloud\AcquiaCloudDataType;

/**
 * Contains all VCS user data provided by the Cloud API.
 */
class AcquiaCloudVcsUserInfo extends AcquiaCloudDataType implements \JsonSerializable {

  /**
   * The user ID.
   *
   * @var int
   */
  private $id;

  /**
   * The user name.
   *
   * @var string
   */
  private $username;

  /**
   * Creates a new instance using the specified user information.
   *
   * @param SvnUser $user
   *   The user data.
   */
  public function __construct(SvnUser $user) {
    $this->setId(intval($user->id()));
    $this->setUsername($user->username());
  }

  /**
   * Gets the user ID.
   *
   * @return int
   *   The ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Sets the user ID.
   *
   * @param int $id
   *   The user ID.
   */
  private function setId($id) {
    if (!is_int($id) || $id <= 0) {
      throw new \InvalidArgumentException('The id parameter must be a positive integer.');
    }
    $this->id = $id;
  }

  /**
   * Gets the user name.
   *
   * @return string
   *   The user name.
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * Sets the user name.
   *
   * @param string $username
   *   The user name.
   */
  private function setUsername($username) {
    if (!is_string($username) || empty($username)) {
      throw new \InvalidArgumentException('The username parameter must be a non-empty string.');
    }
    $this->username = $username;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $result = array(
      'id' => $this->id,
      'username' => $this->username,
    );
    return (object) $result;
  }

}
