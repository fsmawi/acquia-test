<?php

namespace Acquia\WipService\Console\Command;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipService\Console\AbstractWipCtlTest;
use Acquia\WipService\Console\Commands\ThreadPoolCommand;
use Acquia\Wip\Implementation\WipLog;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Missing summary.
 */
class ThreadPoolCommandTest extends AbstractWipCtlTest {

  private $mockThreadPool;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->registerTestingConfig();
    $this->storage = new WipLogStore($this->app);
    $this->wipLog = new WipLog($this->storage);
  }

  /**
   * Missing summary.
   */
  protected function getConsoleApp() {
    $application = $this->app['console'];
    $command = new ThreadPoolCommand();
    $this->mockThreadPool = $this->getMock('Acquia\Wip\Runtime\ThreadPool', array('process'), array(), '', FALSE);
    $this->mockThreadPool->expects($this->once())
      ->method('process');

    $command->swapDependency('acquia.wip.threadpool', $this->mockThreadPool);
    $application->add($command);
    return $application;
  }

  /**
   * Missing summary.
   */
  public function testExecute() {
    $application = $this->getConsoleApp();
    $command = $application->find('process-tasks');
    $command_tester = new CommandTester($command);
    $command_tester->execute(array(
      'command' => $command->getName(),
    ));

    $stdout = $command_tester->getDisplay();
    $this->assertRegExp('/Processing WIP tasks/', $stdout);
  }

  /**
   * Missing summary.
   *
   * @group slow
   */
  public function testSignal() {
    $signals = array(SIGABRT, SIGINT, SIGQUIT, SIGTERM);
    $this->mockThreadPool = $this->getMock('Acquia\Wip\Runtime\ThreadPool', array('stop'));
    $this->mockThreadPool->expects($this->exactly(count($signals)))
      ->method('stop');

    $command = new ThreadPoolCommand();
    $command->swapDependency('acquia.wip.threadpool', $this->mockThreadPool);
    $command->setApplication($this->app['console']);
    $command->setHelperSet($this->app['console']->getHelperSet());

    $input = new StringInput('process-tasks');
    $output = new NullOutput();

    $command->run($input, $output);
    foreach ($signals as $signal) {
      $command->handleSignal($signal);
    }
  }

  /**
   * Missing summary.
   *
   * @expectedException \RuntimeException
   */
  public function testBadLock() {
    // We can't acquire the lock within the same process, as MySQL will always
    // let the same process acquire a lock that it already holds (that is, the
    // same connection ID will succeed in acquiring a lock it already acquired).
    $lock = $this->getMock('Acquia\WipIntegrations\DoctrineORM\MySqlLock');
    $lock->expects($this->once())
      ->method('acquire')
      ->will($this->returnValue(FALSE));

    $command = new ThreadPoolCommand();
    $command->swapDependency('acquia.wip.lock.global', $lock);

    $input = new StringInput('');
    $output = new NullOutput();
    $command->run($input, $output);
  }

}
