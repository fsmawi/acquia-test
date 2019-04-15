<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Iterators\BasicIterator\StateTableIterator;

/**
 * Tests the StateTableIterator.
 */
class StateTableIteratorTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var StateTableIterator
   */
  private $stateTableIterator = NULL;

  /**
   * Setup.
   */
  public function setUp() {
    parent::setUp();
    $this->stateTableIterator = new StateTableIterator();
  }

  /**
   * Tests the constructor.
   */
  public function testConstructor() {
    $this->assertInstanceOf('Acquia\Wip\Iterators\BasicIterator\StateTableIterator', $this->stateTableIterator);
  }

  /**
   * Tests the testTrackTimesForStates.
   */
  public function testTrackTimesForTransitionStates() {
    $timings = $this->stateTableIterator->getTransitionStateTimings();
    $this->assertEmpty($timings);
    $this->stateTableIterator->trackTimesForTransitionStates('', 'start');
    $this->assertEmpty($this->stateTableIterator->getTransitionStateTimings());
    $this->stateTableIterator->trackTimesForTransitionStates('start', 'containerWipStart');
    $timings = $this->stateTableIterator->getTransitionStateTimings();
    $this->assertEquals(TRUE, isset($timings['containerWipStart']));
    $this->assertGreaterThan(0, $timings['containerWipStart']);
    $this->stateTableIterator->trackTimesForTransitionStates('containerWipStart', 'reportPipelinesMetaData');
    $timings = $this->stateTableIterator->getTransitionStateTimings();
    $this->assertEquals(FALSE, isset($timings['containerWipStart']));
    $this->assertEquals(TRUE, isset($timings['reportPipelinesMetaData']));
    $this->assertGreaterThan(0, $timings['reportPipelinesMetaData']);
    $this->stateTableIterator->trackTimesForTransitionStates('reportPipelinesMetaData', 'exportBuildFileAsJson');
    $timings = $this->stateTableIterator->getTransitionStateTimings();
    $this->assertEquals(FALSE, isset($timings['reportPipelinesMetaData']));
    $timings = $this->stateTableIterator->getTransitionStateTimings();
    $this->assertEmpty($timings);
  }

}
