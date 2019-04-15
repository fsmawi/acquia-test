<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

/**
 * Defines an entity for storing Wip max group concurrency records.
 *
 * @Entity @Table(name="wip_group_max_concurrency", options={"engine"="InnoDB"})
 */
class WipGroupMaxConcurrencyEntry {

  /**
   * The group of the Wip object.
   *
   * @var string
   *
   * @Id @Column(type="string", length=255, name="group_name")
   */
  private $groupName;

  /**
   * The maximum number of tasks in this group allowed to be in progress.
   *
   * @var int
   *
   * @Column(type="integer", options={"unsigned"=true}, name="max_count")
   */
  private $maxCount;

  /**
   * Gets the group of the Wip object.
   *
   * @return string
   *   The group of the Wip object.
   */
  public function getGroupName() {
    return $this->groupName;
  }

  /**
   * Sets the group of the Wip object.
   *
   * @param string $group_name
   *   The group of the Wip object.
   */
  public function setGroupName($group_name) {
    if (!is_string($group_name)) {
      throw new \InvalidArgumentException(sprintf(
        'The "group_name" argument must be a string in %s.',
        __METHOD__
      ));
    }
    if (strlen($group_name) > 255) {
      throw new \InvalidArgumentException(sprintf(
        'The "group_name" argument must be 255 characters or less in %s.',
        __METHOD__
      ));
    }

    $this->groupName = $group_name;
  }

  /**
   * Gets the max concurrency value.
   *
   * @return mixed
   *   The max concurrency value.
   */
  public function getMaxCount() {
    return $this->maxCount;
  }

  /**
   * Sets the max concurrency value.
   *
   * @param int $max_count
   *   The max concurrency value.
   */
  public function setMaxCount($max_count) {
    if (!is_int($max_count)) {
      throw new \InvalidArgumentException(sprintf('$max_count argument must be an integer in %s.', __METHOD__));
    }
    if ($max_count < 0 || $max_count > PHP_INT_MAX) {
      throw new \InvalidArgumentException(sprintf('$wid argument out of range (%d) in %s.', $wid, __METHOD__));
    }
    $this->maxCount = $max_count;
  }

}
