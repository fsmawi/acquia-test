<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipService\Console\AbstractWipCtlTest;
use Acquia\WipService\Console\Commands\WipProcessCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Missing summary.
 */
class WipProcessCommandTest extends AbstractWipCtlTest {

  /**
   * Missing summary.
   */
  protected function getConsoleApp() {
    $application = $this->app['console'];
    $application->add(new WipProcessCommand());
    return $application;
  }

  /**
   * Missing summary.
   */
  public function testExecute() {
    $application = $this->getConsoleApp();
    $command = $application->find('process');
    $command_tester = new CommandTester($command);
    $command_tester->execute(array(
      'command' => $command->getName(),
      '--procs' => '3',
    ));

    $stdout = $command_tester->getDisplay();
    $this->assertRegExp('/.*3 processes.*/', $stdout);
  }

}
