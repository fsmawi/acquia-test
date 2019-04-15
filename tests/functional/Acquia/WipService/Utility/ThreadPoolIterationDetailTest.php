<?php

namespace Acquia\WipService\Utility;

use Acquia\Wip\Runtime\ThreadPoolIterationDetail;
use Acquia\Wip\WipFactory;

/**
 * Tests the ThreadPoolIterationDetail class.
 *
 * ThreadPoolProcessDetail was originally written for monitoring iterations of
 * the useThreads method in a ThreadPool object. Here we simply exercise its
 * methods and thus use some fake data to simulate actual usage of the class.
 */
class ThreadPoolIterationDetailTest extends \PHPUnit_Framework_TestCase {
  
  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    WipFactory::setConfigPath('config/config.factory.cfg');
  }

  /**
   * Tests that threads can be tracked, and the total number retrieved.
   */
  public function testReceivedThreads() {
    $iteration_detail = new ThreadPoolIterationDetail(1);

    $this->assertEquals(0, $iteration_detail->getTotalThreads());
    $threads1 = 1;
    $iteration_detail->receivedThreadsToMatch($threads1);
    $threads2 = 2;
    $iteration_detail->receivedThreadsToMatch($threads2);

    $this->assertEquals(
      $threads1 + $threads2,
      $iteration_detail->getTotalThreads()
    );
  }

  /**
   * Tests that sleep time can be set and retrieved.
   */
  public function testSleep() {
    $iteration_detail = new ThreadPoolIterationDetail(1);

    $this->assertEquals(0.0, $iteration_detail->getTotalSleepTime());
    $sleep1 = 1;
    $iteration_detail->recordSleep($sleep1);
    $sleep2 = 2;
    $iteration_detail->recordSleep($sleep2);

    $this->assertEquals(
      $sleep1 + $sleep2,
      $iteration_detail->getTotalSleepTime()
    );
  }

  /**
   * Tests that successful matches can be set and retrieved.
   */
  public function testSuccessfulMatches() {
    $iteration_detail = new ThreadPoolIterationDetail(1);

    $this->assertEquals(0.0, $iteration_detail->getTotalSuccessfulMatches());
    $iteration_detail->recordSuccessfulMatch();
    $iteration_detail->recordSuccessfulMatch();

    $this->assertEquals(2, $iteration_detail->getTotalSuccessfulMatches());
  }

  /**
   * Tests that failed matches can be set and retrieved.
   */
  public function testFailedMatches() {
    $iteration_detail = new ThreadPoolIterationDetail(1);

    $this->assertEquals(0.0, $iteration_detail->getTotalFailedMatches());
    $exception = new \Exception();
    $runtime_exception = new \RuntimeException();
    $iteration_detail->recordFailedMatch($exception);
    $iteration_detail->recordFailedMatch($runtime_exception);
    $this->assertEquals(2, $iteration_detail->getTotalFailedMatches());

    $failures_by_type = array_count_values($iteration_detail->getFailedMatchesByReason());
    $this->assertEquals(1, $failures_by_type['Exception']);
    $this->assertEquals(1, $failures_by_type['RuntimeException']);
  }

  /**
   * Tests that the iteration number can be set and retrieved.
   */
  public function testIteration() {
    $iteration_detail1 = new ThreadPoolIterationDetail(1);
    $iteration_detail2 = new ThreadPoolIterationDetail(2);

    $this->assertEquals(1, $iteration_detail1->getIteration());
    $this->assertEquals(2, $iteration_detail2->getIteration());
  }

  /**
   * Tests that the execution time can be set and retrieved.
   */
  public function testExecutionTime() {
    $iteration_detail1 = new ThreadPoolIterationDetail(1);

    $this->assertEquals(0, $iteration_detail1->getTotalExecutionTime());

    $iteration_detail1->setTotalExecutionTime(12.34);
    $this->assertEquals(12.34, $iteration_detail1->getTotalExecutionTime());

    // Tests rounding.
    $iteration_detail1->setTotalExecutionTime(34.567);
    $this->assertEquals(34.57, $iteration_detail1->getTotalExecutionTime());
  }

}
