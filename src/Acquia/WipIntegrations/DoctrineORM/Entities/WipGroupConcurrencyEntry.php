<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

// @codingStandardsIgnoreStart
/**
 * Defines an entity for storing Wip group concurrency records.
 *
 * @Entity @Table(name="wip_group_concurrency", options={"engine"="InnoDB"}, indexes={
 *   @Index(name="group_name_idx", columns={"group_name"})
 * })
 */
class WipGroupConcurrencyEntry {

  // @codingStandardsIgnoreEnd
  /**
   * The ID of the Wip object.
   *
   * @var int
   *
   * @Id @Column(type="integer", options={"unsigned"=true})
   */
  private $wid;

  /**
   * The group of the Wip object.
   *
   * @var string
   *
   * @Column(type="string", length=255, name="group_name")
   */
  private $groupName;

  /**
   * Gets the Wip object ID.
   *
   * @return int
   *   The Wip object ID.
   */
  public function getWid() {
    return $this->wid;
  }

  /**
   * Sets the Wip object ID.
   *
   * @param int $wid
   *   The Wip object ID.
   */
  public function setWid($wid) {
    if (!is_int($wid)) {
      throw new \InvalidArgumentException(sprintf(
        '$wid argument must be an integer in %s.',
        __METHOD__
      ));
    }
    if ($wid < 1) {
      // Not going to accept zero as a valid wid in case we need that as a
      // special ID for logging.
      throw new \InvalidArgumentException(sprintf(
        '$wid argument must be a positive integer in %s.',
        __METHOD__
      ));
    }
    $this->wid = $wid;
  }

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
        '$group_name argument must be a string in %s.',
        __METHOD__
      ));
    }
    if (strlen($group_name) > 255) {
      throw new \InvalidArgumentException(sprintf(
        '$group_name argument must be 255 characters or less in %s.',
        __METHOD__
      ));
    }

    $this->groupName = $group_name;
  }

}
