<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipService\Console\AbstractWipCtlTest;
use Acquia\WipService\Console\Commands\WipSetMaintenanceCommand;
use Acquia\Wip\State\Maintenance;
use Acquia\Wip\Storage\StateStoreInterface;
use Acquia\Wip\WipFactory;

/**
 * Tests that SetMaintenanceCommand behaves as expected.
 */
class SetMaintenanceCommandTest extends AbstractWipCtlTest {

  /**
   * The state storage instance.
   *
   * @var StateStoreInterface
   */
  private $stateStore;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->registerTestingConfig();
    $this->stateStore = WipFactory::getObject('acquia.wip.storage.state');
  }

  /**
   * Tests that maintenance state is changed correctly.
   */
  public function testMaintenanceState() {
    $this->assertEquals(NULL, $this->stateStore->get(Maintenance::STATE_NAME));

    $tester = $this->executeCommand(new WipSetMaintenanceCommand(), 'set-maintenance', ['--enable' => TRUE]);
    $this->assertEquals(Maintenance::FULL, $this->stateStore->get(Maintenance::STATE_NAME));
    $this->assertContains('Maintenance mode has been enabled', $tester->getDisplay());

    $tester = $this->executeCommand(new WipSetMaintenanceCommand(), 'set-maintenance', ['--enable' => TRUE]);
    $this->assertEquals(Maintenance::FULL, $this->stateStore->get(Maintenance::STATE_NAME));
    $this->assertContains('Maintenance mode is currently enabled', $tester->getDisplay());

    $tester = $this->executeCommand(new WipSetMaintenanceCommand(), 'set-maintenance', []);
    // The above deletes so we check for NULL value.
    $this->assertEquals(NULL, $this->stateStore->get(Maintenance::STATE_NAME));
    $this->assertContains('Maintenance mode has been disabled', $tester->getDisplay());

    $tester = $this->executeCommand(new WipSetMaintenanceCommand(), 'set-maintenance', []);
    // The above deletes so we check for NULL value.
    $this->assertEquals(NULL, $this->stateStore->get(Maintenance::STATE_NAME));
    $this->assertContains('Maintenance mode is currently disabled', $tester->getDisplay());
  }

}
