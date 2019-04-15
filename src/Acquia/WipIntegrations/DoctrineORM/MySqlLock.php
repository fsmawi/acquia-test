<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipService\App;
use Acquia\Wip\LockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Defines an API for working with MySQL-based locks.
 */
class MySqlLock implements LockInterface {

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * Current locks.
   *
   * @var array
   */
  private $locks = array();

  /**
   * The result set mapping.
   *
   * @var ResultSetMapping
   */
  private $rsm;

  /**
   * The prefix used for the lock name.
   *
   * @var string
   */
  private $prefix;

  /**
   * Creates a new instance of MySqlLock.
   */
  public function __construct() {
    // Set up a new scalar mapping for the lock query results.
    $this->rsm = new ResultSetMapping();
    $this->rsm->addScalarResult('lockresult', 'lock');
    $this->prefix = App::getApp()['config.global']['lock_prefix'];
  }

  /**
   * Sets the EntityManagerInterface object used by this MySqlLock instance.
   *
   * @param EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function setEntityManager(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Gets the EntityManagerInterface object used by this MySqlLock instance.
   *
   * @return EntityManagerInterface
   *   The entity manager.
   */
  public function getEntityManager() {
    $result = $this->entityManager;
    if (empty($result)) {
      $result = App::getEntityManager();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function acquire($name, $max_acquire_delay = NULL) {
    if (!isset($max_acquire_delay)) {
      $max_acquire_delay = 0;
    }
    $query = $this->getEntityManager()
      ->createNativeQuery('SELECT GET_LOCK(?, ?) as lockresult', $this->getRsm());

    $query->setParameter(1, $this->prefix($name));
    $query->setParameter(2, $max_acquire_delay);
    $result = $query->getResult();

    $locked = reset($result)['lock'] == 1;

    // Note - the result from the DB is typically the string "1", not int.
    if ($locked) {
      // We can't store a time as it may be incorrect and the db is canonical.
      $this->locks[$name] = $name;
    }

    return $locked;
  }

  /**
   * {@inheritdoc}
   */
  public function release($name) {
    $query = $this->getEntityManager()
      ->createNativeQuery('SELECT RELEASE_LOCK(?) as lockresult', $this->getRsm());
    $query->setParameter(1, $this->prefix($name));
    $result = $query->getResult();
    unset($this->locks[$name]);
    return reset($result)['lock'] == 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isFree($name) {
    $query = $this->getEntityManager()
      ->createNativeQuery('SELECT IS_FREE_LOCK(?) as lockresult', $this->getRsm());
    $query->setParameter(1, $this->prefix($name));
    $result = $query->getResult();

    return reset($result)['lock'] == 1;
  }

  /**
   * Obtains a ResultSetMapping for a lock.
   *
   * @return ResultSetMapping
   *   The ResultSetMapping instance.
   */
  private function getRsm() {
    return $this->rsm;
  }

  /**
   * Prepends a common string onto a lock name.
   *
   * @param string $name
   *   The lock name.
   *
   * @return string
   *   The prefixed lock name.
   */
  private function prefix($name) {
    return "$this->prefix.$name";
  }

  /**
   * {@inheritdoc}
   */
  public function isMine($name) {
    $entity_manager = $this->getEntityManager();
    // IS_LOCK_USED returns the connection ID if it's in use, otherwise NULL.
    $query = $entity_manager
      ->createNativeQuery('SELECT IS_USED_LOCK(?) as lockresult', $this->getRsm());
    $query->setParameter(1, $this->prefix($name));
    $result = $query->getResult();
    $used_lock_id = reset($result)['lock'];
    if (is_null($used_lock_id)) {
      return FALSE;
    }

    // Is it really the current process that is using it?
    $rsm = new ResultSetMapping();
    $rsm->addScalarResult('id', 'id');
    $query = $entity_manager
      ->createNativeQuery('SELECT CONNECTION_ID() AS id', $rsm);
    $result = $query->getResult();
    $connection_id = reset($result)['id'];

    if ($used_lock_id == $connection_id) {
      if (!isset($this->locks[$name])) {
        // Store a record of the lock.
        $this->locks[$name] = $name;
      }
    }
    return $used_lock_id == $connection_id;
  }

}
