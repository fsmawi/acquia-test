<?php

namespace Acquia\Wip\AcquiaCloud;

use Acquia\Wip\WipProcessInterface;
use Acquia\Wip\WipResult;
use Acquia\Wip\WipResultInterface;
use Guzzle\Http\Exception\BadResponseException;

/**
 * The AcquiaCloudResult encapsulates the result of an AcquiaCloud call.
 */
class AcquiaCloudResult extends WipResult implements AcquiaCloudResultInterface {

  const EXIT_CODE_SUCCESS = 200;
  const EXIT_CODE_GENERAL_FAILURE = 509;

  /**
   * The data this result object contains.
   *
   * @var object
   */
  private $data = NULL;

  /**
   * Upon a Cloud API error, this field holds the associated exception message.
   *
   * @var string
   */
  private $error = NULL;

  /**
   * Creates a new instance of AcquiaCloudResult.
   *
   * @param bool $use_dummy_pid
   *   Optional.  If TRUE, a dummy process ID will be created and applied.  This
   *   is useful for Acquia Cloud calls that do not result in a hosting task.
   */
  public function __construct($use_dummy_pid = FALSE) {
    $this->setSuccessExitCodes(array(self::EXIT_CODE_SUCCESS));
    if ($use_dummy_pid) {
      $this->setPid(self::createProcessTaskId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setPid($pid) {
    if (!is_int($pid)) {
      throw new \InvalidArgumentException('The pid parameter must be an integer.');
    }
    parent::setPid($pid);
  }

  /**
   * Sets the result data.
   *
   * @param AcquiaCloudDataType|array|string $data
   *   The data.
   */
  public function setData($data) {
    $this->data = $data;
  }

  /**
   * Gets the result data.
   *
   * @return AcquiaCloudDataType
   *   The data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function toObject($object = NULL) {
    $result = parent::toObject($object);
    $data = $this->getData();
    if (!empty($data)) {
      $result->result->data = $data;
    }
    $error = $this->getError();
    if (!empty($error)) {
      $result->result->error = $error;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function fromObject(
    $object,
    WipResultInterface $wip_result = NULL
  ) {
    $result = $wip_result;
    if (empty($result)) {
      $result = new AcquiaCloudResult();
    } elseif (!$wip_result instanceof AcquiaCloudResultInterface) {
      throw new \InvalidArgumentException(
        'The wip_result parameter must be an instance of AcquiaCloudResultInterface.'
      );
    }
    parent::fromObject($object, $result);
    if (isset($object->result)) {
      if (isset($object->result->data)) {
        $result->setData($object->result->data);
      }
      if (!empty($object->result->error)) {
        $result->setError($object->result->error);
      }
    }
    return $result;
  }

  /**
   * Sets the error message associated with this result.
   *
   * If $error is a BadResponseException, $this->exitMessage will be set
   * to the reason phrase from the response while $this->error will be
   * set to the exception message.
   *
   * If $error is another Exception, both $this->error and $this->exitMessage
   * will be set to the exception message.
   *
   * If $error is a string, both $this->exitMessage and $this->error will be
   * set to $error.
   *
   * @param string|\Exception $error
   *   The error.
   */
  public function setError($error) {
    $error_message = NULL;
    $exit_code = self::EXIT_CODE_GENERAL_FAILURE;
    if ($error instanceof \Exception) {
      if ($error instanceof BadResponseException && ($response = $error->getResponse())) {
        $exit_code = $response->getStatusCode();
        $error_message = $response->getReasonPhrase();
        $error = $error->getMessage();
      } else {
        $error_message = $error->getMessage();
        $error = $error_message;
      }
    }
    $this->error = strval($error);
    try {
      $this->setExitCode($exit_code);
    } catch (\Exception $e) {
      // The exit code has already been set.
    }
    try {
      $this->setExitMessage($error_message);
    } catch (\Exception $e) {
      // The exit message has already been set.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getError() {
    return $this->error;
  }

  /**
   * {@inheritdoc}
   */
  public static function createUniqueId($tid) {
    return $tid;
  }

  /**
   * {@inheritdoc}
   */
  public function populateFromProcess(WipProcessInterface $process) {
    if (!$process instanceof AcquiaCloudProcessInterface) {
      throw new \InvalidArgumentException('The process parameter must implement AcquiaCloudProcessInterface');
    }
    $result_exit_codes = $this->getSuccessExitCodes();
    $process_exit_codes = $process->getSuccessExitCodes();
    // Set the success exit codes from the process, but only if they have been
    // set on the process and have not been customized  on this result.
    if (!empty($process_exit_codes) && (empty($result_exit_codes) ||
        count(array_diff($result_exit_codes, array(self::EXIT_CODE_SUCCESS))) == 0)
    ) {
      $this->setSuccessExitCodes($process_exit_codes);
    }
    parent::populateFromProcess($process);
    $this->setError($process->getError());
  }

  /**
   * Creates a process ID that can be used for tasks with no associated process.
   *
   * @return int
   *   The process ID.
   */
  public static function createProcessTaskId() {
    // Create a dummy result ID.  For results representing the result of a
    // hosting task, this value will be replaced by the actual hosting task ID.
    return (mt_rand(~PHP_INT_MAX, -1));
  }

}
