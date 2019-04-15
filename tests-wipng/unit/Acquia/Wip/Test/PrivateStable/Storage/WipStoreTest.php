<?php

namespace Acquia\Wip\Test;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class WipStoreTest extends \PHPUnit_Framework_TestCase {

  /**
   * The WipStore instance.
   *
   * @var \Acquia\Wip\Storage\WipStore
   */
  private $wipStore;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->wipStore = WipFactory::getObject('acquia.wip.storage.wip');
    $this->wipStore->initialize();
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipStoreSaveException
   */
  public function testAddInvalidWip() {
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $this->wipStore->save(1, $iterator);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddInvalidWipId() {
    $wip = new BasicWip();
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);

    $this->wipStore->save(-1, $iterator);
  }

  /**
   * Missing summary.
   */
  public function testAddWip() {
    $wip = new BasicWip();
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);

    $wip_id = rand();
    // Store Wip iterator.
    $this->wipStore->save($wip_id, $iterator);

    // Ensure it was store properly.
    $iterator_check = $this->wipStore->get($wip_id);
    $this->assertNotEmpty($iterator_check);
  }

  /**
   * Missing summary.
   */
  public function testWipIncludeFiles() {
    // Ensure that the class in the test include file is not available yet.
    $this->assertFalse(class_exists('\Acquia\Wip\Test\PrivateStable\Storage\WipStoreTestIncludeFile'));

    $wip_id = rand();
    $wip = new BasicWip();
    $wip->addInclude(__DIR__, 'test/WipStoreTestIncludeFile.inc');
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $this->wipStore->save($wip_id, $iterator);

    $this->wipStore->get($wip_id);
    $this->assertTrue(class_exists('\Acquia\Wip\Test\PrivateStable\Storage\WipStoreTestIncludeFile'));
  }

  /**
   * Missing summary.
   */
  public function testWipDataDelete() {
    // Store Wips.
    $wip1 = new BasicWip();
    $iterator1 = WipFactory::getObject('acquia.wip.iterator');
    $iterator1->initialize($wip1);
    $wip_id1 = rand();
    $this->wipStore->save($wip_id1, $iterator1);

    // Ensure it was store properly.
    $iterator_check = $this->wipStore->get($wip_id1);
    $this->assertNotEmpty($iterator_check);

    $wip2 = new BasicWip();
    $iterator2 = WipFactory::getObject('acquia.wip.iterator');
    $iterator2->initialize($wip2);
    $wip_id2 = rand();
    $this->wipStore->save($wip_id2, $iterator2);

    // Ensure it was store properly.
    $iterator_check = $this->wipStore->get($wip_id2);
    $this->assertNotEmpty($iterator_check);

    // Remove one Wip.
    $this->wipStore->remove($wip_id1);

    // Ensure the removed Wip can not be found.
    $wip_data = $this->wipStore->get($wip_id1);
    $this->assertEmpty($wip_data);

    // Ensure the preserved Wip can be found.
    $wip_data = $this->wipStore->get($wip_id2);
    $this->assertNotEmpty($wip_data);
  }

}
