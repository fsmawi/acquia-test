<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Container\ContainerProcess;
use Acquia\Wip\Container\ContainerResult;
use Acquia\Wip\Container\NullContainer;
use Acquia\Wip\Environment;
use Acquia\Wip\Implementation\ContainerApi;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Iterators\BasicIterator\WipContext;
use Acquia\Wip\WipContainerInterface;
use Acquia\Wip\WipContextInterface;

/**
 * Missing summary.
 */
class ContainerApiTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testSetContainerProcess() {
    $container_api = new ContainerApi();
    $context = new WipContext();
    $wip_log = new WipLog();

    // Attempt to set multiple container processes and verify that only the last
    // one remains.
    for ($i = 0; $i < 3; $i++) {
      $pid = sprintf('pid%d', mt_rand());
      $start_time = time();
      $process = $this->createContainerProcess($pid, $start_time);
      $container_api->setContainerProcess($process, $context, $wip_log);
    }
    $id = ContainerResult::createUniqueId($pid, $start_time);
    $actual_process = $container_api->getContainerProcess($id, $context);
    $this->assertCount(1, $container_api->getContainerProcesses($context));
    $this->assertEquals($process, $actual_process);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testAddContainerProcess() {
    $count = 5;
    $context = new WipContext();
    $processes = $this->addContainerProcesses($context, $count);

    $container_api = new ContainerApi();
    $actual_processes = $container_api->getContainerProcesses($context);
    $this->assertNotEmpty($actual_processes);
    $this->assertCount($count, $actual_processes);

    foreach ($processes as $id => $expected_process) {
      $this->assertEquals($processes[$id], $actual_processes[$id]);
    }
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testRemoveContainerProcess() {
    $count = 5;
    $context = new WipContext();
    $processes = $this->addContainerProcesses($context, $count);
    $ids = array_keys($processes);

    $container_api = new ContainerApi();
    $wip_log = new WipLog();
    foreach ($processes as $id => $process) {
      // Check that the remaining unique IDs of the processes match those in the
      // context.
      $this->assertEquals(
        array_values($ids),
        array_keys($container_api->getContainerProcesses($context))
      );
      // Check that the expected number of processes were added to the context.
      $this->assertCount($count, $container_api->getContainerProcesses($context));

      $container_api->removeContainerProcess($process, $context, $wip_log);
      // Check that the processes in the context have decreased by 1.
      $this->assertCount(--$count, $container_api->getContainerProcesses($context));

      // Check that the removed process is no longer in the context.
      $this->assertArrayNotHasKey($id, $container_api->getContainerProcesses($context));

      // Check that the expected processes still exist in the context.
      $key = array_search($id, $ids);
      unset($ids[$key]);
      $this->assertEquals(
        array_values($ids),
        array_keys($container_api->getContainerProcesses($context))
      );
    }
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testClearContainerProcesses() {
    $container_api = new ContainerApi();
    $context = new WipContext();
    $wip_log = new WipLog();
    $count = 2;
    $processes = $this->addContainerProcesses($context, $count);
    $container_api->clearContainerProcesses($context, $wip_log);
    $this->assertCount(0, $container_api->getContainerProcesses($context));
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testReleaseServerSideResourcesCompletedProcess() {
    $container_api = new ContainerApi();
    $context = new WipContext();
    $wip_log = new WipLog();

    $process = $this->getContainerProcessMock();
    $process->expects($this->once())
      ->method('hasCompleted')
      ->will($this->returnValue(TRUE));
    $process->expects($this->once())
      ->method('release')
      ->will($this->returnValue(NULL));
    $process->expects($this->never())
      ->method('kill')
      ->will($this->returnValue(NULL));
    $container_api->addContainerProcess($process, $context);
    $container_api->removeContainerProcess($process, $context, $wip_log);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testReleaseServerSideResourcesRunningProcess() {
    $container_api = new ContainerApi();
    $context = new WipContext();
    $wip_log = new WipLog();

    $process = $this->getContainerProcessMock();
    $process->expects($this->once())
      ->method('hasCompleted')
      ->will($this->returnValue(FALSE));
    $process->expects($this->never())
      ->method('release')
      ->will($this->returnValue(NULL));
    $process->expects($this->once())
      ->method('kill')
      ->will($this->returnValue(NULL));
    $container_api->addContainerProcess($process, $context);
    $container_api->removeContainerProcess($process, $context, $wip_log);
  }

  /**
   * Gets a ContainerProcess instance mock.
   *
   * @return PHPUnit_Framework_MockObject_MockObject
   *   The mock instance of ContainerProcessInterface.
   */
  private function getContainerProcessMock() {
    $pid = sprintf('pid%d', mt_rand());
    $start_time = time();

    $methods = array(
      'hasCompleted',
      'kill',
      'release',
      'getWipId',
      'getPid',
      'getStartTime',
    );
    $process = $this->getMock('\Acquia\Wip\Container\ContainerProcess', $methods);
    $process->expects($this->any())
      ->method('getPid')
      ->will($this->returnValue($pid));
    $process->expects($this->any())
      ->method('getStartTime')
      ->will($this->returnValue($start_time));

    return $process;
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testSetContainerResult() {
    $pid = sprintf('pid%d', mt_rand());
    $start_time = time();
    $result = $this->createContainerResult($pid, $start_time);

    $container_api = new ContainerApi();
    $context = new WipContext();
    $wip_log = new WipLog();
    $container_api->setContainerResult($result, $context, $wip_log);

    $id = ContainerResult::createUniqueId($pid, $start_time);
    $actual_result = $container_api->getContainerResult($id, $context);
    $this->assertNotEmpty($actual_result);
    $this->assertEquals($result, $actual_result);
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testAddContainerResults() {
    $count = 5;
    $context = new WipContext();
    $processes = $this->addContainerProcesses($context, $count);

    $container_api = new ContainerApi();
    $actual_processes = $container_api->getContainerProcesses($context);
    $this->assertNotEmpty($actual_processes);
    $this->assertCount($count, $actual_processes);

    foreach ($processes as $id => $expected_process) {
      $this->assertEquals($processes[$id], $actual_processes[$id]);
    }
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testRemoveContainerResult() {
    $container_api = new ContainerApi();
    $context = new WipContext();
    $wip_log = new WipLog();
    $count = 5;
    $results = $this->addContainerResults($context, $count);
    $ids = array_keys($results);

    foreach ($results as $id => $result) {
      // Check that the remaining process IDs match those in the context.
      $this->assertEquals(
        array_values($ids),
        array_keys($container_api->getContainerResults($context))
      );

      // Check that the expected number of processes were added to the context.
      $this->assertCount($count, $container_api->getContainerResults($context));

      $container_api->removeContainerResult($result, $context, $wip_log);

      // Check that the results in the context have decreased by 1.
      $this->assertCount(--$count, $container_api->getContainerResults($context));

      // Check that the removed result is no longer in the context.
      $this->assertArrayNotHasKey($id, $container_api->getContainerResults($context));

      // Check that the expected results still exist in the context.
      $key = array_search($id, $ids);
      unset($ids[$key]);
      $this->assertEquals(
        array_values($ids),
        array_keys($container_api->getContainerResults($context))
      );
    }
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testClearContainerResults() {
    $container_api = new ContainerApi();
    $context = new WipContext();
    $wip_log = new WipLog();
    $count = 2;

    $results = $this->addContainerResults($context, $count);
    $processes = $this->addContainerProcesses($context, $count);

    $container_api->clearContainerResults($context, $wip_log);
    $this->assertCount(0, $container_api->getContainerResults($context));
    $this->assertCount(0, $container_api->getContainerProcesses($context));
  }

  /**
   * Creates a ContainerProcess instance.
   *
   * @param string $pid
   *   The process PID.
   * @param int $start_time
   *   The timestamp of when the process was started.
   *
   * @return ContainerProcessInterface
   *   The ContainerProcess instance.
   */
  private function createContainerProcess($pid, $start_time) {
    $process = new ContainerProcess();
    $process->setPid($pid);
    $process->setStartTime($start_time);
    $process->setContainer(new NullContainer());
    return $process;
  }

  /**
   * Creates a ContainerResult instance.
   *
   * @param string $pid
   *   The process PID.
   * @param int $start_time
   *   The timestamp of when the process was started.
   *
   * @return ContainerResultInterface
   *   The ContainerResult instance.
   */
  private function createContainerResult($pid, $start_time) {
    $result = new ContainerResult();
    $result->setPid($pid);
    $result->setStartTime($start_time);
    return $result;
  }

  /**
   * Adds a given number of generated ContainerProcess instances to the context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where container processes are recorded.
   * @param int $count
   *   The number of container processes to add to the context.
   *
   * @return ContainerProcessInterface[]
   *   An array, keyed by process ID, with ContainerProcessInterface instances
   *   as the values.
   */
  private function addContainerProcesses(WipContextInterface $context, $count) {
    $container_api = new ContainerApi();
    $wip_log = new WipLog();

    $processes = array();
    for ($i = 0; $i < $count; $i++) {
      $pid = sprintf('pid%d', mt_rand());
      $start_time = time();

      $id = ContainerResult::createUniqueId($pid, $start_time);
      $processes[$id] = $this->createContainerProcess($pid, $start_time);
      $container_api->addContainerProcess($processes[$id], $context, $wip_log);
    }
    return $processes;
  }

  /**
   * Adds a given number of generated ContainerResult instances to the context.
   *
   * @param WipContextInterface $context
   *   The WipContextInterface instance where container results are recorded.
   * @param int $count
   *   The number of container results to add to the context.
   *
   * @return ContainerResultInterface[]
   *   An array, keyed by process ID, with ContainerResultInterface instances as
   *   the values.
   */
  private function addContainerResults(WipContextInterface $context, $count) {
    $container_api = new ContainerApi();
    $wip_log = new WipLog();

    $results = array();
    for ($i = 0; $i < $count; $i++) {
      $pid = sprintf('pid%d', mt_rand());
      $start_time = time();

      $id = ContainerResult::createUniqueId($pid, $start_time);
      $results[$id] = $this->createContainerResult($pid, $start_time);
      $container_api->addContainerResult($results[$id], $context, $wip_log);
    }
    return $results;
  }

  /**
   * Tests that the appropriate transition value is returned.
   *
   * @param bool $started
   *   Whether the container has started.
   * @param bool $configured
   *   Whether the container has been configured.
   * @param null|int $exit_code
   *   The process exit code:
   *   '0' - Indicates success.
   *   '1' - Indicates failure.
   * @param string $transition
   *   The transition.
   *
   * @group ContainerApi
   *
   * @dataProvider transitionProvider
   */
  public function testGetContainerStatus($started, $configured, $exit_code, $transition) {
    $pid = sprintf('pid%d', mt_rand());

    $container = new NullContainer();
    $container->setStarted($started);
    $container->setConfigured($configured);

    $process = new ContainerProcess();
    $process->setPid($pid);
    $process->setStartTime(time());
    $process->setContainer($container);
    $process->setEnvironment(Environment::getRuntimeEnvironment());

    if ($exit_code !== NULL) {
      $container_result = new ContainerResult();
      $container_result->setStartTime(time());
      $container_result->setEndTime(time());
      $container_result->setSuccessExitCodes(array(0));
      $container_result->setExitCode($exit_code);
      $process->setResult($container_result);
    }

    $context = new WipContext();
    $wip_log = new WipLog();
    $container_api = new ContainerApi();
    $container_api->setContainerProcess($process, $context, $wip_log);
    $actual_transition = $container_api->getContainerStatus($context, $wip_log);
    $this->assertEquals($transition, $actual_transition);
  }

  /**
   * Provides parameters for checking transition values.
   *
   * @see $this->testGetContainerStatus()
   *
   * @return array
   *   A two-dimensional array, with the inner arrays containing the following
   *   elements:
   *   - 'started':    If the container has been started yet.
   *   - 'configured': If the container has been configured yet.
   *   - 'exit_code':  The exit code of the process (if completed).
   *   - 'transition': The transition value we expect to be returned.
   */
  public function transitionProvider() {
    return array(
      array(FALSE, FALSE, NULL, 'wait'),
      array(TRUE, FALSE, NULL, 'ready'),
      array(TRUE, TRUE, NULL, 'running'),
      array(TRUE, TRUE, 0, 'success'),
      array(TRUE, TRUE, 1, 'fail'),
    );
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   */
  public function testWipId() {
    $wip_id = 15;
    $api = new ContainerApi();
    $api->setWipId($wip_id);
    $this->assertEquals($wip_id, $api->getWipId());
  }

  /**
   * Missing summary.
   *
   * @group ContainerApi
   *
   * @expectedException \InvalidArgumentException
   */
  public function testWipWrongType() {
    $wip_id = '15';
    $api = new ContainerApi();
    $api->setWipId($wip_id);
  }

}
