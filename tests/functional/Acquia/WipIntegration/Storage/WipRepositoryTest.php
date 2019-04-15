<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\WipIntegrations\DoctrineORM\WipStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class WipRepositoryFunctionalTest extends AbstractFunctionalTest {

  /**
   * The WipStore instance.
   *
   * @var \Acquia\Wip\Storage\WipStore
   */
  private $wipStore;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    WipFactory::setConfigPath('config/config.factory.test.cfg');

    $this->wipStore = WipFactory::getObject('acquia.wip.storage.wip');
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipStoreSaveException
   */
  public function testAddInvalidWip() {
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $result = $this->wipStore->save(1, $iterator);
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

    $result = $this->wipStore->save(-1, $iterator);
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
    $this->assertFalse(class_exists('\Acquia\WipService\Test\WipRepositoryTestIncludeFile'));

    $wip_id = rand();
    $wip = new BasicWip();
    $wip->addInclude(__DIR__, 'test/WipRepositoryTestIncludeFile.inc');
    $iterator = WipFactory::getObject('acquia.wip.iterator');
    $iterator->initialize($wip);
    $this->wipStore->save($wip_id, $iterator);

    $check_wip = $this->wipStore->get($wip_id);
    $this->assertTrue(class_exists('\Acquia\WipService\Test\WipRepositoryTestIncludeFile'));
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
