<?php

namespace Acquia\WipIntegrations;

use Acquia\WipIntegrations\DoctrineORM\MySqlLock;
use Acquia\WipIntegrations\MockQuery;

/**
 * Missing summary.
 */
class MySqlLockTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var MySqlLock
   */
  private $lock;

  /**
   * Missing summary.
   */
  public function setup() {
    $this->lock = new MySqlLock();
  }

  /**
   * Missing summary.
   */
  public function testGetLock() {
    $db = $this->getLockMock(1);
    $this->lock->setEntityManager($db);

    $result = $this->lock->acquire('anything');
    $this->assertEquals(TRUE, $result);
    $this->assertInternalType('bool', $result);

    $db = $this->getLockMock(0);
    $this->lock->setEntityManager($db);

    $result = $this->lock->acquire('anything');
    $this->assertNotEquals(TRUE, $result);
    $this->assertInternalType('bool', $result);

    $db = $this->getLockMock(NULL);
    $this->lock->setEntityManager($db);

    $result = $this->lock->acquire('anything');
    $this->assertNotEquals(TRUE, $result);
    $this->assertInternalType('bool', $result);
  }

  /**
   * Missing summary.
   */
  public function testReleaseLock() {

    $db = $this->getLockMock(1);
    $this->lock->setEntityManager($db);

    $result = $this->lock->release('anything');
    $this->assertEquals(TRUE, $result);
    $this->assertInternalType('bool', $result);

    $db = $this->getLockMock(0);
    $this->lock->setEntityManager($db);

    $result = $this->lock->release('anything');
    $this->assertNotEquals(TRUE, $result);
    $this->assertInternalType('bool', $result);

    $db = $this->getLockMock(NULL);
    $this->lock->setEntityManager($db);

    $result = $this->lock->release('anything');
    $this->assertNotEquals(TRUE, $result);
    $this->assertInternalType('bool', $result);
  }

  /**
   * Missing summary.
   */
  public function testLockIsFree() {

    $db = $this->getLockMock(1);
    $this->lock->setEntityManager($db);

    $result = $this->lock->isFree('anything');
    $this->assertEquals(TRUE, $result);
    $this->assertInternalType('bool', $result);

    $db = $this->getLockMock(0);
    $this->lock->setEntityManager($db);

    $result = $this->lock->isFree('anything');
    $this->assertNotEquals(TRUE, $result);
    $this->assertInternalType('bool', $result);

    $db = $this->getLockMock(NULL);
    $this->lock->setEntityManager($db);

    $result = $this->lock->isFree('anything');
    $this->assertNotEquals(TRUE, $result);
    $this->assertInternalType('bool', $result);
  }

  /**
   * Obtains a mock DoctrineORM EntityManager that always returns fixed results.
   *
   * More specifically, the mock EntityManager object always returns a query
   * from createNativeQuery, which always returns the given result. This is used
   * for simulating return values from MySQL's locking functions.
   *
   * @param mixed $result
   *   The mock result.
   *
   * @return PHPUnit_Framework_MockObject_MockObject
   *   The mock result.
   */
  private function getLockMock($result) {
    $mock_query = new MockQuery($result);

    $mockdb = $this->getMock('\Doctrine\ORM\EntityManager', array('createNativeQuery'), array(), '', FALSE);
    $mockdb->expects($this->exactly(1))
      ->method('createNativeQuery')
      ->will($this->returnValue($mock_query));

    return $mockdb;
  }

}
