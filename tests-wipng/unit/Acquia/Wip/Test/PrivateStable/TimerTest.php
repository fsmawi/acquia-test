<?php

namespace Acquia\Wip\Test;

use Acquia\Wip\AdjustableTimer;
use Acquia\Wip\Timer;

/**
 * Missing summary.
 */
class TimerTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   */
  public function testStartAndStop() {
    $timer = new AdjustableTimer();

    $this->assertEmpty($timer->getCurrentTimerName());

    $timer->start('system');
    $this->assertEquals($timer->getCurrentTimerName(), 'system');
    $timer->adjustStart(-3);

    $timer->start('user');
    $this->assertEquals($timer->getCurrentTimerName(), 'user');
    $this->assertGreaterThan(2, $timer->getTime('system'));

    $timer->adjustStart(-1);
    $timer->start('cloud');
    $timer->adjustStart(-1);
    $user_time = $timer->getTime('user');
    $timer->start('user');
    $timer->adjustStart(-1);
    $timer->start('user');
    $this->assertEquals($timer->getTime('user'), $user_time);
    $timer->adjustStart(-1);
    $timer->start('cloud');
    $this->assertGreaterThan(2, $timer->getTime('user'));

    $names = $timer->getTimerNames();
    $this->assertContains('system', $names);
    $this->assertContains('user', $names);
    $this->assertContains('cloud', $names);

    $timer->stop();
    $this->assertEmpty($timer->getCurrentTimerName());
  }

  /**
   * Missing summary.
   */
  public function testGetTime() {
    $timer = new AdjustableTimer();
    $timer->start('user');
    $timer->adjustStart(-5);
    $timer->start('cloud');

    $seconds = $timer->getTime('user');
    $milliseconds = $timer->getTimeMs('user');
    $this->assertEquals($milliseconds, (int) ($seconds * 1000));
  }

  /**
   * Missing summary.
   *
   * @expectedException InvalidArgumentException
   */
  public function testNonexistentTimer() {
    $timer = new Timer();

    $timer->getTime('user');
  }

  /**
   * Missing summary.
   *
   * @expectedException InvalidArgumentException
   */
  public function testNonexistentTimerMs() {
    $timer = new Timer();

    $timer->getTimeMs('user');
  }

  /**
   * Missing summary.
   *
   * @expectedException InvalidArgumentException
   */
  public function testInvalidNewTimerName() {
    $timer = new Timer();

    $timer->start('');
    $timer->stop();
  }

  /**
   * Missing summary.
   *
   * @expectedException InvalidArgumentException
   */
  public function testInvalidJson() {
    $timers = array('system' => 2, 'user' => 3, 6 => 4);
    $json = json_encode($timers);
    $timer = Timer::fromJson($json);
  }

  /**
   * Missing summary.
   */
  public function testJson() {
    $timers = array('system' => 2, 'user' => 3, 'cloud' => 4);
    $json = json_encode($timers);

    $timer = Timer::fromJson($json);
    $this->assertEquals($json, $timer->toJson());
  }

  /**
   * Missing summary.
   */
  public function testBlend() {
    $augend = Timer::fromJson(json_encode(array('user' => 3, 'cloud' => 4)));
    $addend = Timer::fromJson(json_encode(array('system' => 5, 'user' => 6)));

    $augend->blend($addend);

    $this->assertEquals($augend->getTime('user'), 9);
    $this->assertEquals($augend->getTime('cloud'), 4);
    $this->assertEquals($augend->getTime('system'), 5);
  }

  /**
   * Missing summary.
   */
  public function testReport() {
    $timer = Timer::fromJson(json_encode(array('user' => 3.14, 'cloud' => 1.592)));
    $report = $timer->report();
    $expected = sprintf("The user timer took 00:00:03.14\n");
    $expected .= sprintf("The cloud timer took 00:00:01.59\n");
    $this->assertEquals($expected, $report);
  }

}
