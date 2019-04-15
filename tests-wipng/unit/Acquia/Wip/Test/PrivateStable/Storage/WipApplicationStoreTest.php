<?php

namespace Acquia\Wip\Test\PrivateStable\Storage;

use Acquia\Wip\Runtime\WipApplication;
use Acquia\Wip\WipApplicationStatus;

/**
 * Missing summary.
 */
class WipApplicationStoreTest extends \PHPUnit_Framework_TestCase {

  /**
   * The WipApplicationStore instance.
   *
   * @var \Acquia\Wip\Storage\WipApplicationStore
   */
  private $wipApplicationStore;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->wipApplicationStore = \Acquia\Wip\WipFactory::getObject('acquia.wip.storage.wipapplication');
    $this->wipApplicationStore->initialize();
  }

  /**
   * Missing summary.
   */
  public function testWipApplicationAdd() {
    $application = new WipApplication();
    $application->setHandler('1234-5678-abcd-efgh-ijkl');
    $application->setStatus(WipApplicationStatus::ENABLED);
    // Add the wip application.
    $this->wipApplicationStore->save($application);

    // Ensure it was stored properly.
    $application_check = $this->wipApplicationStore->get($application->getId());
    $this->assertNotEmpty($application_check);
    $this->assertEquals($application->getId(), $application_check->getId());
    $this->assertEquals($application->getHandler(), $application_check->getHandler());
    $this->assertEquals($application->getStatus(), $application_check->getStatus());
  }

  /**
   * Missing summary.
   */
  public function testWipApplicationUpdate() {
    $application = new WipApplication();
    $application->setHandler('1234-5678-abcd-efgh-ijkl');
    $application->setStatus(WipApplicationStatus::DISABLED);

    // Add the wip application.
    $this->wipApplicationStore->save($application);

    // Ensure it was stored properly.
    $application_check = $this->wipApplicationStore->get($application->getId());
    $this->assertNotEmpty($application_check);
    $this->assertEquals($application->getId(), $application_check->getId());
    $this->assertEquals($application->getHandler(), $application_check->getHandler());
    $this->assertEquals($application->getStatus(), $application_check->getStatus());

    $application->setHandler('0987-6543-zyxw-vutsr-qpon');
    $application->setStatus(WipApplicationStatus::ENABLED);
    // Update the wip application.
    $this->wipApplicationStore->save($application);

    // Ensure it was stored properly.
    $application_check = $this->wipApplicationStore->get($application->getId());
    $this->assertNotEmpty($application_check);
    $this->assertEquals($application->getId(), $application_check->getId());
    $this->assertEquals($application->getHandler(), $application_check->getHandler());
    $this->assertEquals($application->getStatus(), $application_check->getStatus());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidWipApplicationAdd() {
    $application = new WipApplication();
    $this->wipApplicationStore->save($application);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\WipApplicationStoreSaveException
   */
  public function testDuplicateHandler() {
    $application = new WipApplication();
    $application->setHandler('1234-5678-abcd-efgh-ijkl');
    $this->wipApplicationStore->save($application);

    $application = new WipApplication();
    $application->setHandler('1234-5678-abcd-efgh-ijkl');
    $this->wipApplicationStore->save($application);
  }

  /**
   * Missing summary.
   */
  public function testWipApplicationGetByHandler() {
    $handlers = array();
    for ($i = 0; $i < 10; $i++) {
      $application = new WipApplication();
      $handler = $i . '-' . md5(mt_rand());
      $handlers[] = $handler;
      $application->setHandler($handler);
      $this->wipApplicationStore->save($application);
    }

    $find = array_rand($handlers);

    $application = $this->wipApplicationStore->getByHandler($handlers[$find]);
    $this->assertNotEmpty($application);
    $this->assertEquals($handlers[$find], $application->getHandler());
  }

  /**
   * Missing summary.
   */
  public function testRemoveWipApplication() {
    // Store some wip applications.
    $application_keep1 = new WipApplication();
    $application_keep1->setHandler('1-' . md5(mt_rand()));
    $this->wipApplicationStore->save($application_keep1);
    $application_check = $this->wipApplicationStore->get($application_keep1->getId());
    $this->assertNotEmpty($application_check);

    $application_delete = new WipApplication();
    $application_delete->setHandler('2-' . md5(mt_rand()));
    $this->wipApplicationStore->save($application_delete);
    $application_check = $this->wipApplicationStore->get($application_delete->getId());
    $this->assertNotEmpty($application_check);

    $application_keep2 = new WipApplication();
    $application_keep2->setHandler('3-' . md5(mt_rand()));
    $this->wipApplicationStore->save($application_keep2);
    $application_check = $this->wipApplicationStore->get($application_keep2->getId());
    $this->assertNotEmpty($application_check);

    // Remove a wip application.
    $this->wipApplicationStore->remove($application_delete);

    // Ensure that the proper wip application got deleted.
    $application_check = $this->wipApplicationStore->get($application_keep1->getId());
    $this->assertNotEmpty($application_check);
    $application_check = $this->wipApplicationStore->get($application_delete->getId());
    $this->assertEmpty($application_check);
    $application_check = $this->wipApplicationStore->get($application_keep2->getId());
    $this->assertNotEmpty($application_check);
  }

}
