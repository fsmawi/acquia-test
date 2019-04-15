<?php

namespace Acquia\Wip\Objects\Verify;

use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\ExitMessage;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Ssh\SshResultInterface;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Verifies the SSH service is working properly.
 */
class VerifySsh extends BasicWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The state table that will be executed by this Wip object.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
start {
  * configureEnvironment
}

configureEnvironment {
  * verifySynchronousSsh
}

# Test a basic synchronous SSH call.
verifySynchronousSsh:checkSshStatus {
   success verifySynchronousSshValue
   * failure
}

verifySynchronousSshValue:checkValue {
  success verifyCheckSshStatusUninitialized
  * failure
}

# Verify the checkSshStatus transition method returns uninitialized.
verifyCheckSshStatusUninitialized:checkSshStatus {
  uninitialized verifyCheckSshStatusFail
  * failure
}

# Verify the checkSshStatus identifies failure.
verifyCheckSshStatusFail:checkSshStatus {
  fail verifyAsynchronousSsh
  * failure
}

verifyAsynchronousSsh:checkSshStatus {
  success verifyAsynchronousSshValue
  wait verifyAsynchronousSsh wait=30 exec=false
  * failure
}

verifyAsynchronousSshValue:checkValue {
  success verifyLongAsynchronousSsh
  * failure
}

verifyLongAsynchronousSsh:checkSshStatus {
  success verifyLongAsynchronousSshExecutionTime
  wait verifyLongAsynchronousSsh wait=60 exec=false
  * failure
}

# Verify the signal was received and processed within the expected amount
# of time.
verifyLongAsynchronousSshExecutionTime:checkExecutionTime {
  success verifyLongAsynchronousSshValue
  fail failure
}

verifyLongAsynchronousSshValue:checkValue {
  success finish
  * failure
}

failure {
  * finish
  ! finish
}

EOT;

  /**
   * The environment.
   *
   * @var EnvironmentInterface
   */
  private $environment = NULL;

  /**
   * {@inheritdoc}
   */
  public function start(WipContextInterface $wip_context) {
    $iterator = $wip_context->getIterator();
    $state_names = array(
      'verifySynchronousSshValue' => 'verifySynchronousSsh',
      'verifyAsynchronousSshValue' => 'verifyAsynchronousSsh',
      'verifyLongAsynchronousSshValue' => 'verifyLongAsynchronousSsh',
      'verifyLongAsynchronousSshExecutionTime' => 'verifyLongAsynchronousSsh',
    );
    foreach ($state_names as $state => $linked_state) {
      $context = $iterator->getWipContext($state);
      $context->linkContext($linked_state);
    }
  }

  /**
   * Creates an Environment instance that will be used for the tests.
   */
  public function configureEnvironment() {
    // Create the local environment for running commands locally.
    $env = Environment::getRuntimeEnvironment();
    $env->setServers(array('localhost'));
    $env->selectNextServer();
    $this->environment = $env;
  }

  /**
   * Verify that synchronous SSH calls work properly.
   *
   * Synchronous calls do not use a callback; the data is returned from the
   * call itself.  This method tests that the result of the call has the
   * expected value.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function verifySynchronousSsh(WipContextInterface $wip_context) {
    $expected_value = sprintf('value_%d', mt_rand());
    $this->setExpectedValue($wip_context, $expected_value);
    $ssh = $this->getSsh('Perform a simple synchronous SSH call.', $this->environment);
    $ssh_result = $ssh->execCommand(sprintf('echo "%s"', $expected_value));
    $this->getSshApi()->setSshResult($ssh_result, $wip_context, $this->getWipLog());

    // If there is a failure, have the exit message ready.  This will be
    // overwritten as the code progresses such that any failure will result in
    // a relevant exit message.
    $failure_summary = sprintf('Synchronous SSH call failed.');
    $failure_detail = sprintf('Synchronous SSH call failed.  Expected value %s.', $expected_value);
    $this->setExitMessage(new ExitMessage($failure_summary, WipLogLevel::FATAL, $failure_detail));
  }

  /**
   * The transition method does all of the work.
   */
  public function verifySynchronousSshValue() {
  }

  /**
   * Tests a situation in which there is no SshResult instance.
   */
  public function verifyCheckSshStatusUninitialized() {
    // Do not put an SSH result into the context.
  }

  /**
   * Tests the case in which the SSH command failed.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function verifyCheckSshStatusFail(WipContextInterface $wip_context) {
    $directory_does_not_exist = sprintf('/dir_does_not_exist/%d', mt_rand());

    $ssh = $this->getSsh('Execute a command that will fail.', $this->environment);
    $ssh_result = $ssh->execCommand(sprintf('ls -l "%s"', $directory_does_not_exist));
    $this->getSshApi()->setSshResult($ssh_result, $wip_context, $this->getWipLog());

    // If there is a failure, have the exit message ready.  This will be
    // overwritten as the code progresses such that any failure will result in
    // a relevant exit message.
    $fail_message = sprintf('Synchronous failing SSH call was not interpreted as a failure.');
    $this->setExitMessage(new ExitMessage($fail_message, WipLogLevel::FATAL));
  }

  /**
   * Verifies that asynchronous SSH calls are working properly.
   *
   * This particular call will complete so fast that the signal is irrelevant.
   * As a result, this call will test the fail-safe poll detects call completion
   * and returns the proper SSH result.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function verifyAsynchronousSsh(WipContextInterface $wip_context) {
    $expected_value = sprintf('value_%d', mt_rand());
    $this->setExpectedValue($wip_context, $expected_value);
    $ssh = $this->getSsh('Perform a simple asynchronous SSH call.', $this->environment);
    $ssh_process = $ssh->execAsyncCommand(sprintf('echo "%s"', $expected_value));
    $this->getSshApi()->setSshProcess($ssh_process, $wip_context, $this->getWipLog());

    // If there is a failure, have the exit message ready.  This will be
    // overwritten as the code progresses such that any failure will result in
    // a relevant exit message.
    $fail_summary = sprintf('Short asynchronous SSH call failed.');
    $fail_detail = sprintf('Short asynchronous SSH call failed.  Expected value %s.', $expected_value);
    $this->setExitMessage(new ExitMessage($fail_summary, WipLogLevel::FATAL, $fail_detail));
  }

  /**
   * The transition method does all of the work.
   */
  public function verifyAsynchronousSshValue() {
  }

  /**
   * This method tests a long SSH call value is retrieved from a signal.
   *
   * The fail-safe timeout for this method must be significantly longer than
   * the call execution.  If the signal is not received, the result will not be
   * retrieved until the fail-safe poll time is reached.  This is detected when
   * verifying the execution time in a subsequent step and will cause this Wip
   * object to fail with an appropriate message.
   *
   * If the signal is received, the elapsed time between the SSH call and the
   * verification of the time will be significantly shorter than the fail-safe
   * poll time, resulting in success.
   *
   * The value of the result will also be verified in a subsequent step.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function verifyLongAsynchronousSsh(WipContextInterface $wip_context) {
    $expected_value = sprintf('value_%d', mt_rand());
    $expected_time = 10;
    $this->setExpectedValue($wip_context, $expected_value);
    $this->setExpectedTime($wip_context, $expected_time);
    $ssh = $this->getSsh('Perform a simple long asynchronous SSH call.', $this->environment);
    $ssh_process = $ssh->execAsyncCommand(sprintf('sleep %d && echo "%s"', $expected_time, $expected_value));
    $this->getSshApi()->setSshProcess($ssh_process, $wip_context, $this->getWipLog());

    // If there is a failure, have the exit message ready.  This will be
    // overwritten as the code progresses such that any failure will result in
    // a relevant exit message.
    $fail_summary = 'Long asynchronous SSH call failed.';
    $fail_detail = sprintf('Long asynchronous SSH call failed.  Expected value %s.', $expected_value);
    $this->setExitMessage(new ExitMessage($fail_summary, WipLogLevel::FATAL, $fail_detail));
  }

  /**
   * The transition method does all of the work.
   */
  public function verifyLongAsynchronousSshExecutionTime() {
  }

  /**
   * The transition method does all of the work.
   */
  public function verifyLongAsynchronousSshValue() {
  }

  /**
   * Verifies the SSH stdout value matches the expected value.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
   *   'success' - The value returned from SSH matched the anticipated value.
   *   'fail' - The value did not match the anticipated value.
   */
  public function checkValue(WipContextInterface $wip_context) {
    $result = 'fail';
    try {
      $ssh_api = $this->getSshApi();
      $ssh_results = $ssh_api->getSshResults($wip_context);
      if (count($ssh_results) !== 1) {
        $message = sprintf('Unexpected number of SSH results in the context: %d.', count($ssh_results));
        $this->log(WipLogLevel::ERROR, $message);
      } else {
        /** @var SshResultInterface $ssh_result */
        $ssh_result = reset($ssh_results);
        $expected_value = $this->getExpectedValue($wip_context);
        if (trim($ssh_result->getStdout()) === $expected_value) {
          $result = 'success';
        }
      }
    } catch (\Exception $e) {
      $message = sprintf('The checkValue method encountered an unexpected error: %s.', $e->getMessage());
      $this->log(WipLogLevel::ERROR, $message);
    }
    return $result;
  }

  /**
   * Verifies the SSH call executed in the expected time frame.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
   *   'success' - The value returned from SSH matched the anticipated value.
   *   'fail' - The value did not match the anticipated value.
   */
  public function checkExecutionTime(WipContextInterface $wip_context) {
    $result = 'fail';
    try {
      $ssh_api = $this->getSshApi();
      $ssh_results = $ssh_api->getSshResults($wip_context);
      if (count($ssh_results) !== 1) {
        $message = sprintf('Unexpected number of SSH results in the context: %d.', count($ssh_results));
        $this->log(WipLogLevel::ERROR, $message);
      } else {
        /** @var SshResultInterface $ssh_result */
        $ssh_result = reset($ssh_results);
        $expected_time = $this->getExpectedTime($wip_context);
        $elapsed_time = time() - $ssh_result->getStartTime();

        // Be a bit lenient on the time because this method will not execute
        // exactly when the asynchronous command stops.  The fail-safe poll
        // should be significantly higher than the expected time, so a large
        // difference here will mean we didn't receive the signal or the
        // signal failed to interrupt the wait.
        if ($elapsed_time < ($expected_time * 1.3)) {
          $result = 'success';
          $message = 'The elapsed time was within tolerance of the expected time. The signal was received. Expected: %d seconds; Elapsed: %d seconds.';
          $this->log(
            WipLogLevel::INFO,
            sprintf($message, $expected_time, $elapsed_time)
          );
        } else {
          $fail_summary = 'Asynchronous SSH call seems to have missed a signal.';
          $message = 'The elapsed time was measurably larger than the expected time - seems to have missed a signal. Expected: %d seconds; Elapsed: %d seconds.';
          $fail_detail = sprintf(
            $message,
            $expected_time,
            $elapsed_time
          );
          $this->setExitMessage(new ExitMessage($fail_summary, WipLogLevel::FATAL, $fail_detail));
        }
      }
    } catch (\Exception $e) {
      $message = sprintf('The checkExecutionTime method encountered an unexpected error: %s.', $e->getMessage());
      $this->log(WipLogLevel::ERROR, $message);
    }
    return $result;
  }

  /**
   * Sets the expected value into the specified WipContext instance.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   * @param string $expected_value
   *   The expected value.
   */
  private function setExpectedValue(WipContextInterface $wip_context, $expected_value) {
    if (!is_string($expected_value)) {
      throw new \InvalidArgumentException('The expected_value parameter must be a string.');
    }
    // @noinspection PhpUndefinedFieldInspection.
    $wip_context->expectedValue = $expected_value;
  }

  /**
   * Gets the expected value from the specified WipContext instance.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
   *   The expected value.
   *
   * @throws \RuntimeException
   *   If the expected value has not been set into the context.
   */
  private function getExpectedValue(WipContextInterface $wip_context) {
    if (!isset($wip_context->expectedValue)) {
      throw new \RuntimeException('The expected value has not been set into the specified context.');
    }
    return $wip_context->expectedValue;
  }

  /**
   * Sets the expected execution time into the specified WipContext instance.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   * @param int $expected_time
   *   The expected execution time, measured in seconds.
   */
  private function setExpectedTime(WipContextInterface $wip_context, $expected_time) {
    if (!is_int($expected_time) || $expected_time < 0) {
      throw new \InvalidArgumentException('The expected_int parameter must be a positive integer.');
    }
    // @noinspection PhpUndefinedFieldInspection.
    $wip_context->expectedTime = $expected_time;
  }

  /**
   * Gets the expected execution time from the specified WipContext instance.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return int
   *   The expected execution time.
   *
   * @throws \RuntimeException
   *   If the expected time has not been set into the context.
   */
  private function getExpectedTime(WipContextInterface $wip_context) {
    $result = NULL;
    if (!isset($wip_context->expectedTime)) {
      throw new \RuntimeException('The expected execution time has not been set into the specified context.');
    }
    $result = $wip_context->expectedTime;
    return $result;
  }

}
