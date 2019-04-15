<?php

namespace Acquia\WipService\Utility;

use Acquia\Wip\Runtime\ThreadPoolProcessDetail;
use Acquia\Wip\WipFactory;

/**
 * Tests the ThreadPoolProcessDetail class.
 *
 * ThreadPoolProcessDetail was originally written for monitoring parts of the
 * ThreadPool object. Here we simply exercise its methods and thus use a fake
 * process ID to simulate actual usage of the class.
 */
class ThreadPoolProcessDetailTest extends \PHPUnit_Framework_TestCase {

  /**
   * The pid of the "class" under test.
   *
   * @var int
   */
  private $pid = 123;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    WipFactory::setConfigPath('config/config.factory.cfg');
  }

  /**
   * Tests that sleep time can be set and retrieved.
   */
  public function testSleep() {
    $process_detail = new ThreadPoolProcessDetail($this->pid);

    $this->assertEquals(0.0, $process_detail->getTotalSleepTime());
    $sleep = 20.0;
    $process_detail->recordSleep($sleep);
    $this->assertEquals($sleep, $process_detail->getTotalSleepTime());
  }

  /**
   * Tests that run time can be set and retrieved.
   */
  public function testRunTime() {
    $process_detail = new ThreadPoolProcessDetail($this->pid);

    $this->assertEquals(0.0, $process_detail->getTotalRunTime());
    $runtime = 20.0;
    $process_detail->setTotalRunTime($runtime);
    $this->assertEquals($runtime, $process_detail->getTotalRunTime());
  }

  /**
   * Tests that interacting with IterationDetail objects works correctly.
   */
  public function testAddIterationDetails() {
    $process_detail = new ThreadPoolProcessDetail($this->pid);

    $this->assertNull($process_detail->getCurrentIterationDetail());
    $process_detail->addNewIterationDetails();
    $this->assertNotNull($process_detail->getCurrentIterationDetail());
    $this->assertEquals(1, $process_detail->getIteration());
  }

  /**
   * Tests that reports can be generated correctly.
   */
  public function testReport() {
    $process_detail = new ThreadPoolProcessDetail($this->pid);

    $process_detail->addNewIterationDetails();
    // Create some IterationDetail objects with some data.
    $sleep1 = 15.0;
    $threads1 = 10;
    $execution_time1 = 10.0;
    $process_detail->getCurrentIterationDetail()->recordSleep($sleep1);
    $process_detail->getCurrentIterationDetail()->receivedThreadsToMatch($threads1);
    $process_detail->getCurrentIterationDetail()->setTotalExecutionTime($execution_time1);
    $exception = new \Exception();
    $runtime_exception = new \RuntimeException();
    $process_detail->getCurrentIterationDetail()->recordFailedMatch($exception);
    $process_detail->getCurrentIterationDetail()->recordFailedMatch($runtime_exception);
    $process_detail->getCurrentIterationDetail()->recordSuccessfulMatch();

    $process_detail->addNewIterationDetails();
    $sleep2 = 15.0;
    $execution_time2 = 20.0;
    $threads2 = 20;
    $process_detail->getCurrentIterationDetail()->recordSleep($sleep2);
    $process_detail->getCurrentIterationDetail()->receivedThreadsToMatch($threads2);
    $process_detail->getCurrentIterationDetail()->setTotalExecutionTime($execution_time2);
    $process_detail->getCurrentIterationDetail()->recordSuccessfulMatch();

    $process_detail->setTotalRunTime(60.0);

    $expectedVerboseOutput = <<<EOT

Processing tasks on pid 123:
  Total run time: 60 seconds
  Total sleep time: 30 seconds
  Total execution time: 30 seconds
  Total tasks found: 0
  Total tasks matched and executed: 2
  Total failed task/thread matches: 2
     Exception: 1
     RuntimeException: 1
  Total iterations of useThreads: 2
  Average threads found per iteration: 15
  Percentage of time spent in execution: 50%
  useThreads iteration 1
     Execution time: $execution_time1 seconds
     Sleep time: $sleep1 seconds
     Threads found: $threads1
     Tasks found: 0
     Tasks matched and executed: 1
     Failed task/thread matches: 2
        Exception: 1
        RuntimeException: 1
  useThreads iteration 2
     Execution time: $execution_time2 seconds
     Sleep time: $sleep2 seconds
     Threads found: $threads2
     Tasks found: 0
     Tasks matched and executed: 1
     Failed task/thread matches: 0
EOT;

    $logger = $this->getMockBuilder('Acquia\Wip\Implementation\WipLog')
      ->setMethods(array('log'))
      ->getMock();
    $logger->expects($this->once())
      ->method('log')
      ->with($this->anything(), $expectedVerboseOutput);
    $process_detail->setLogger($logger);

    // Use a config file that does not have logging enabled.
    $process_detail->report();
    // Use a config file that has verbose mode enabled.
    WipFactory::setConfigPath('config/config.factory.test.cfg');
    $process_detail->report();
  }

  /**
   * Tests that a division by zero situation returns 0 instead.
   */
  public function testDivisionByZero() {
    $process_detail = new ThreadPoolProcessDetail($this->pid);

    $process_detail->addNewIterationDetails();
    // Percentage of time spent in execution requires the total runtime as a
    // divisor.
    $process_detail->setTotalRunTime(0);

    $expectedVerboseOutput = <<<EOT

Processing tasks on pid 123:
  Total run time: 0 seconds
  Total sleep time: 0 seconds
  Total execution time: 0 seconds
  Total tasks found: 0
  Total tasks matched and executed: 0
  Total failed task/thread matches: 0
     
  Total iterations of useThreads: 1
  Average threads found per iteration: 0
  Percentage of time spent in execution: 0%
  useThreads iteration 1
     Execution time: 0 seconds
     Sleep time: 0 seconds
     Threads found: 0
     Tasks found: 0
     Tasks matched and executed: 0
     Failed task/thread matches: 0
EOT;

    $logger = $this->getMockBuilder('Acquia\Wip\Implementation\WipLog')
      ->setMethods(array('log'))
      ->getMock();
    $logger->expects($this->once())
      ->method('log')
      ->with($this->anything(), $expectedVerboseOutput);
    $process_detail->setLogger($logger);

    // Use a config file that has verbose mode enabled.
    WipFactory::setConfigPath('config/config.factory.test.cfg');
    $process_detail->report();
  }

}
