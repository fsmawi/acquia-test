<?php

namespace Acquia\WipService\Test;

use Symfony\Component\Process\Process;

/**
 * Missing summary.
 */
class MySqlLockTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var Process
   */
  private $proc1;

  /**
   * Missing summary.
   *
   * @var Process
   */
  private $proc2;

  private $tempfile = '';

  /**
   * Missing summary.
   */
  public function setUp() {

    parent::setUp();

    $this->tempfile = tempnam('/tmp', 'locktest');
    // The bootstrap will attempt to hold the lock to exactly 5 seconds. This
    // process would run for a maximum of 300 seconds, but we intend to always
    // kill it once we're finished.
    $command = sprintf('exec php %s/../../../../resource/bootstrap_locktest.php 300 %s', __DIR__, $this->tempfile);
    $this->proc1 = new Process($command . ' PROC1');
    $this->proc2 = new Process($command . ' PROC2');

    $this->proc1->start();
    $this->proc2->start();

    // Give the test procs a chance to start and set up signal handlers.
    sleep(1);
  }

  /**
   * Missing summary.
   */
  public function tearDown() {
    parent::tearDown();

    unlink($this->tempfile);
  }

  /**
   * Missing summary.
   *
   * @group excluded
   */
  public function testBasicLock() {
    // Send signals to both processes.
    $this->proc1->signal(SIGUSR1);


    // This pause is here because signals sent near-simultaneously may be
    // processed in any order.  The mutual exclusivity test is done below
    // (which requires no pause between signals), but here, we verify that proc1
    // gets the lock, so we need to make sure that it does.
    usleep(50000);

    $this->proc2->signal(SIGUSR1);

    // Small amount of time (50ms) for signal handling.
    usleep(50000);
    // Terminate both now we're done.
    $this->proc1->signal(SIGABRT);
    $this->proc2->signal(SIGABRT);

    $this->proc1->wait();
    $this->proc2->wait();

    $out1 = $this->proc1->getOutput();
    $out2 = $this->proc2->getOutput();

    // The first process should say that it got the lock, the second should have
    // failed to do so.
    $this->assertRegExp('/LOCK ACQUIRE: true/', $out1);
    $this->assertRegExp('/LOCK ACQUIRE: false/', $out2);
    $this->assertNotRegExp('/LOCK ACQUIRE: true/', $out2);
  }

  /**
   * Missing summary.
   *
   * @group excluded
   */
  public function testRelease() {
    // Send signals to both processes.
    $this->proc1->signal(SIGUSR1);
    // Small amount of time for signal handling.
    usleep(50000);
    // Signal proc1 to release.
    $this->proc1->signal(SIGUSR2);
    usleep(50000);
    // Proc2 should be able to acquire.
    $this->proc2->signal(SIGUSR1);

    // Small amount of time for signal handling.
    usleep(50000);

    // Terminate both now we're done.
    $this->proc1->signal(SIGABRT);
    $this->proc2->signal(SIGABRT);

    $this->proc1->wait();
    $this->proc2->wait();

    $out1 = $this->proc1->getOutput();
    $out2 = $this->proc2->getOutput();

    // The first process should say that it got the lock, and released it. The
    // second should then have been able to get the lock.
    $this->assertRegExp('/LOCK ACQUIRE: true/', $out1);
    $this->assertRegExp('/LOCK RELEASE/', $out1);
    $this->assertRegExp('/LOCK ACQUIRE: true/', $out2);
  }

  /**
   * Missing summary.
   *
   * @group excluded
   */
  public function testReleaseAll() {
    // Acquire the lock.
    $this->proc1->signal(SIGUSR1);
    usleep(50000);
    // Attempt to acquire and fail.
    $this->proc2->signal(SIGUSR1);

    // Small amount of time for signal handling.
    usleep(50000);

    $out1 = $this->proc1->getIncrementalOutput();
    $out2 = $this->proc2->getIncrementalOutput();

    // The first process should say that it got the lock, and not proc2 ...
    $this->assertRegExp('/LOCK ACQUIRE: true/', $out1);
    $this->assertNotRegExp('/LOCK ACQUIRE: true/', $out2);

    // Terminate the one holding the lock.
    $this->proc1->signal(SIGABRT);
    // Small amount of time for signal handling.
    usleep(50000);

    // First proc should now have released all locks as it exited.
    $out1 = $this->proc1->getIncrementalOutput();
    $this->assertRegExp('/LOCK RELEASE ALL/', $out1);

    // Proc2 should be able to acquire.
    $this->proc2->signal(SIGUSR1);

    // We're done - kill it so we don't have to wait 7 sec.
    usleep(50000);

    // Proc2 should then have been able to get the lock.
    $out2 = $this->proc2->getIncrementalOutput();
    $this->assertRegExp('/LOCK ACQUIRE: true/', $out2);

    $this->proc2->signal(SIGABRT);

    $this->proc1->wait();
    $this->proc2->wait();
  }

  /**
   * Test on many runs that only 1 of 2 procs can get a lock.
   *
   * Attempts to get the lock are as close to simlutaneous as possible on a
   * single machine.
   *
   * @group slow
   * @group excluded
   */
  public function testExclusive() {
    file_put_contents('/tmp/locks', "\n", FILE_APPEND);

    // Was trying 1000 iterations, but it took too long.
    for ($i = 0; $i < 300; ++$i) {
      // Wipe the file - symfony process incremental output doesn't seem precise
      // enough for testing timing, so we're logging each full iteration to a
      // file.
      file_put_contents($this->tempfile, '');

      $this->proc1->signal(SIGUSR1);
      $this->proc2->signal(SIGUSR1);

      // Note that the signals are sent near-simultaneously, but can be
      // received in any order still (with a bias toward 1 being processed
      // first).  This pause is only here to ensure there is time to process
      // them at all.  Without this pause, we would be testing that processes
      // grabbed the lock before either of them had time to do so.
      usleep(50000);

      // Release both.
      $this->proc1->signal(SIGUSR2);
      $this->proc2->signal(SIGUSR2);

      // Slightly longer pause to ensure all signals got processed before next
      // loop.
      usleep(500000);

      // Get an array keyed by the lines.
      $output = array_map('trim', file($this->tempfile));
      // Should always be 4 lines of output, otherwise we have trouble
      // verifying.
      $this->assertCount(4, $output);

      // Check no change after unique and flip.
      $output = array_unique($output);
      $this->assertCount(4, $output);
      $output = array_flip($output);
      $this->assertCount(4, $output);

      // Check that exactly one of the procs managed to get the lock.
      $acquire_counter = 0;
      if (isset($output['PROC1:ACQ'])) {
        $this->assertArrayNotHasKey('PROC2:ACQ', $output);
        ++$acquire_counter;
      }
      if (isset($output['PROC2:ACQ'])) {
        $this->assertArrayNotHasKey('PROC1:ACQ', $output);
        ++$acquire_counter;
      }
      $this->assertEquals(1, $acquire_counter);
    }

    // All done - kill both.
    $this->proc1->signal(SIGABRT);
    $this->proc2->signal(SIGABRT);
  }

  /**
   * Exact repeat of testExclusive with the acquire signal order randomized.
   *
   * @group slow
   * @group excluded
   */
  public function testExclusiveRandomized() {
    file_put_contents('/tmp/locks', "\n", FILE_APPEND);

    // Was trying 1000 iterations, but it took too long.
    for ($i = 0; $i < 300; ++$i) {
      // Wipe the file - symfony process incremental output doesn't seem precise
      // enough for testing timing, so we're logging each full iteration to a
      // file.
      file_put_contents($this->tempfile, '');

      if (rand(0, 1)) {
        $this->proc1->signal(SIGUSR1);
        $this->proc2->signal(SIGUSR1);
      } else {
        $this->proc2->signal(SIGUSR1);
        $this->proc1->signal(SIGUSR1);
      }

      // This pause is only here to ensure there is time to process the signals.
      // Without this pause, we would be testing that processes grabbed the lock
      // before either of them had time to do anything with the signal.
      usleep(50000);

      // Release both.
      $this->proc1->signal(SIGUSR2);
      $this->proc2->signal(SIGUSR2);

      // Slightly longer pause to ensure all signals got processed before next
      // loop.
      usleep(500000);

      // Get an array keyed by the lines.
      $output = array_map('trim', file($this->tempfile));
      // Should always be 4 lines of output, otherwise we have trouble
      // verifying.
      $this->assertCount(4, $output);

      // Check no change after unique and flip.
      $output = array_unique($output);
      $this->assertCount(4, $output);
      $output = array_flip($output);
      $this->assertCount(4, $output);

      // Check that exactly one of the procs managed to get the lock.
      $acquire_counter = 0;
      if (isset($output['PROC1:ACQ'])) {
        $this->assertArrayNotHasKey('PROC2:ACQ', $output);
        ++$acquire_counter;
      }
      if (isset($output['PROC2:ACQ'])) {
        $this->assertArrayNotHasKey('PROC1:ACQ', $output);
        ++$acquire_counter;
      }
      $this->assertEquals(1, $acquire_counter);
    }

    // All done - kill both.
    $this->proc1->signal(SIGABRT);
    $this->proc2->signal(SIGABRT);
  }

  /**
   * Missing summary.
   *
   * @group slow
   *
   * @group excluded
   */
  public function testIsMine() {
    // Signal first to acquire.
    $this->proc1->signal(SIGUSR1);

    // Small amount of time for signal processing.
    usleep(50000);

    // Signal second to acquire (should fail).
    $this->proc2->signal(SIGUSR1);

    // Signal first to check it still had the original lock.
    $this->proc1->signal(SIGHUP);

    // Wait for longer than the default lock lease time.
    sleep(6);

    // Signal 2 to acquire again, without 1 releasing the lock.
    $this->proc2->signal(SIGUSR1);
    // Small amount of time for signal processing.
    usleep(50000);

    // Signal first to check it still had the original lock - this time we
    // should find that it no longer has the lock.  The lock exists, but is
    // held by proc 2 and hence has a different ID.
    $this->proc1->signal(SIGHUP);
    $this->proc2->signal(SIGHUP);

    // Terminate both now we're done.
    $this->proc1->signal(SIGABRT);
    $this->proc2->signal(SIGABRT);
    $this->proc1->wait();
    $this->proc2->wait();
    $out1 = $this->proc1->getOutput();
    $out2 = $this->proc2->getOutput();

    // Just grab the "IS MINE" lines from output from proc 1 into an array.
    preg_match_all('/^PROC1:IS MINE:(.+)$/m', $out1, $matches);

    // We should have "IS MINE:YES", followed by "IS MINE:NO" on the first proc.
    $this->assertEquals('YES', trim($matches[1][0]));
    $this->assertEquals('NO', trim($matches[1][1]));
    $this->assertCount(2, $matches[1]);

    preg_match_all('/^PROC2:((?:IS MINE| LOCK ACQUIRE):.+)$/m', $out2, $matches);

    // We first want to see failure to acquire the lock in proc2, then success,
    // then a confirmation that proc2 holds the lock.
    $this->assertEquals('LOCK ACQUIRE: false', trim($matches[1][0]));
    $this->assertEquals('LOCK ACQUIRE: true', trim($matches[1][1]));
    $this->assertEquals('IS MINE:YES', trim($matches[1][2]));
  }

}
