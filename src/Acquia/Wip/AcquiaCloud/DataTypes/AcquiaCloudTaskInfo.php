<?php

namespace Acquia\Wip\AcquiaCloud\DataTypes;

use Acquia\Cloud\Api\Response\Task;
use Acquia\Wip\AcquiaCloud\AcquiaCloudDataType;

/**
 * Represents a hosting task.
 */
class AcquiaCloudTaskInfo extends AcquiaCloudDataType implements \JsonSerializable {

  /**
   * The task state value that indicates successful completion.
   */
  const DONE = 'done';

  /**
   * The task state value that indicates unsuccessful completion.
   */
  const ERROR = 'error';

  /**
   * The task state value that indicates the task was killed.
   */
  const KILLED = 'killed';

  /**
   * The task state value that indicates the task has been received.
   */
  const RECEIVED = 'received';

  /**
   * The task state value that indicates the task has been started.
   */
  const STARTED = 'started';

  /**
   * The task state value that indicates unsuccessful completion.
   */
  const FAILED = 'failed';

  /**
   * The task state value that indicates the error has been acknowledged.
   */
  const ERROR_ACKNOWLEDGED = 'error_acked';

  /**
   * The task ID.
   *
   * @var int
   */
  private $id;

  /**
   * The hosting task queue name.
   *
   * @var string
   */
  private $queue;

  /**
   * The state of the task.
   *
   * @var string
   */
  private $state;

  /**
   * The task description.
   *
   * @var string
   */
  private $description;

  /**
   * The Unix timestamp indicating when the task was created.
   *
   * @var int
   */
  private $created;

  /**
   * The Unix timestamp indicating when the task was started.
   *
   * @var int
   */
  private $started;

  /**
   * The Unix timestamp indicating when the task was completed.
   *
   * @var int
   */
  private $completed;

  /**
   * The user that invoked the task.
   *
   * @var string
   */
  private $sender;

  /**
   * The result of the task.
   *
   * @var array
   */
  private $result;

  /**
   * Any cookies that are associated with this task.
   *
   * @var string[]
   */
  private $cookies;

  /**
   * Log messages associated with this task.
   *
   * @var string
   */
  private $logs;

  /**
   * Creates an object of type AcquiaCloudTaskInfo.
   *
   * The object may be a subclass of AcquiaCloudTaskInfo that can be used to
   * interpret the result sent back from Hosting.
   *
   * @param Task $task
   *   The task.
   * @param string $result_class_name
   *   Optional. If provided, this class name will be used to instantiate the
   *   info object.
   *
   * @return AcquiaCloudTaskInfo
   *   The newly instantiated task info object.
   */
  public static function instantiate(Task $task, $result_class_name = NULL) {
    $result = NULL;
    if (!empty($result_class_name) && is_string($result_class_name) && class_exists($result_class_name)) {
      try {
        $result = new $result_class_name($task);
        if (!$result instanceof AcquiaCloudTaskInfo) {
          // This class is the incorrect type.
          $result = NULL;
        }
      } catch (\Exception $e) {
      }
    }
    if (NULL === $result) {
      $result = new AcquiaCloudTaskInfo($task);
    }
    return $result;
  }

  /**
   * Creates a new instance of AcquiaCloudTaskInfo initialized from the Task.
   *
   * @param Task $task
   *   The task.
   */
  public function __construct(Task $task = NULL) {
    if (!empty($task)) {
      $this->initialize($task);
    }
  }

  /**
   * Initializes this instance from the values in the specified Task.
   *
   * @param Task $task
   *   The task.
   */
  public function initialize(Task $task) {
    $this->setId(intval($task->id()));
    $this->setQueue($task->queue());
    $this->setState($task->state());
    $this->setDescription($task->description());
    $created_time = $task->created();
    if (!empty($created_time)) {
      $this->setCreated($created_time->getTimestamp());
    }
    $this->setStarted($task->startTime());
    $this->setCompleted($task->completedTime());
    $this->setSender($task->sender());
    $this->setResult($task->result());
    $this->setCookies($task->cookie());
    $this->setLogs($task->logs());
    if ($this->isFailure()) {
      $completed = $this->getCompleted();
      if (empty($completed)) {
        // When the task is killed, the completed field is not always populated.
        // This field is how we know that the task is no longer running, and the
        // status indicates the result.
        $this->setCompleted(time());
      }
    }
  }

  /**
   * Sets the task ID.
   *
   * @param int $id
   *   The task ID.
   */
  private function setId($id) {
    if (!is_int($id) || $id <= 0) {
      throw new \InvalidArgumentException(sprintf(
        'The id field must be a positive integer; %s provided',
        $id
      ));
    }
    $this->id = $id;
  }

  /**
   * Gets the task ID.
   *
   * @return int
   *   The task ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the name of the hosting task queue.
   *
   * @return string
   *   The hosting queue name.
   */
  public function getQueue() {
    return $this->queue;
  }

  /**
   * Sets the hosting task queue name.
   *
   * @param string $queue
   *   The queue name.
   */
  private function setQueue($queue) {
    if (!is_string($queue) || empty($queue)) {
      throw new \InvalidArgumentException('The queue parameter must be a non-empty string.');
    }
    $this->queue = $queue;
  }

  /**
   * Gets the task state.
   *
   * The task state can be one of DONE, ERROR, KILLED, RECEIVED, STARTED,
   * FAILED, or ERROR_ACKNOWLEDGED.
   *
   * @return string
   *   The task state.
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Sets the task state.
   *
   * @param string $state
   *   The task state.
   */
  private function setState($state) {
    if (!is_string($state) || empty($state)) {
      throw new \InvalidArgumentException('The state parameter must be a non-empty string.');
    }
    $this->state = $state;
  }

  /**
   * Gets the task description.
   *
   * @return string
   *   The task description.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Sets the task description.
   *
   * @param string $description
   *   The task description.
   */
  private function setDescription($description) {
    if (!is_string($description) || empty($description)) {
      throw new \InvalidArgumentException('The description parameter must be a non-empty string.');
    }
    $this->description = $description;
  }

  /**
   * Gets the Unix timestamp representing the time this task was created.
   *
   * @return int
   *   The Unix timestamp indicating the creation time.
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Sets the Unix timestamp representing the time this task was created.
   *
   * @param int $created
   *   The Unix timestamp indicating the creation time.
   */
  private function setCreated($created) {
    if (!is_int($created) || $created <= 0) {
      throw new \InvalidArgumentException('The created parameter must be a non-negative integer.');
    }
    $this->created = $created;
  }

  /**
   * Gets the Unix timestamp indicating when this task was started.
   *
   * @return int
   *   The Unix timestamp.
   */
  public function getStarted() {
    return $this->started;
  }

  /**
   * Sets the Unix timestamp indicating when this task was started.
   *
   * @param int $started
   *   The Unix timestamp.
   */
  private function setStarted($started) {
    if (!is_int($started) || $started < 0) {
      throw new \InvalidArgumentException('The started parameters must be a non-negative integer.');
    }
    $this->started = $started;
  }

  /**
   * Gets the Unix timestamp indicating when this task was completed.
   *
   * @return int
   *   The Unix timestamp.
   */
  public function getCompleted() {
    return $this->completed;
  }

  /**
   * Sets the Unix timestamp indicating when this task was completed.
   *
   * @param int $completed
   *   The Unix timestamp.
   */
  private function setCompleted($completed) {
    if (!is_int($completed) || $completed < 0) {
      throw new \InvalidArgumentException('the completed parameter must be a non-negative integer.');
    }
    $this->completed = $completed;
  }

  /**
   * Gets the user that created this task.
   *
   * @return string
   *   The task creator.
   */
  public function getSender() {
    return $this->sender;
  }

  /**
   * Sets the user that created this task.
   *
   * @param string $sender
   *   The task creator.
   */
  private function setSender($sender) {
    if (!is_string($sender)) {
      throw new \InvalidArgumentException('The sender parameter must be a string.');
    }
    $this->sender = $sender;
  }

  /**
   * Gets the task result.
   *
   * Note: most tasks do not expose a result in this field.
   *
   * @return array
   *   The result.
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Sets the task result.
   *
   * @param array $result
   *   The result.
   */
  private function setResult($result) {
    if (is_string($result) && !empty($result)) {
      $result = json_decode($result, TRUE);
    } else {
      $result = array();
    }
    $this->result = $result;
  }

  /**
   * Gets any cookies associated with this task.
   *
   * @return string[]
   *   The cookies.
   */
  public function getCookies() {
    return $this->cookies;
  }

  /**
   * Sets cookies that are associated with this task.
   *
   * @param string[] $cookies
   *   The cookies.
   */
  private function setCookies($cookies) {
    if (is_string($cookies) && !empty($cookie)) {
      $cookies = json_decode($cookies, TRUE);
    } else {
      $cookies = array();
    }
    $this->cookies = $cookies;
  }

  /**
   * Gets log messages associated with this task.
   *
   * Log message entries are separated by a newline character.
   *
   * @return string
   *   The log messages.
   */
  public function getLogs() {
    return $this->logs;
  }

  /**
   * Sets log messages associated with this task.
   *
   * @param string $logs
   *   The log messages.
   */
  private function setLogs($logs) {
    if (!is_string($logs)) {
      throw new \InvalidArgumentException('The logs parameter must be a string.');
    }
    $this->logs = $logs;
  }

  /**
   * Indicates whether the task execution was successful.
   *
   * @return bool
   *   TRUE if the task execution was successful; FALSE otherwise.protected
   */
  public function isSuccess() {
    $result = TRUE;
    $completed = $this->getCompleted();
    if (empty($completed) || $this->isFailure()) {
      $result = FALSE;
    }
    return $result;
  }

  /**
   * Indicates whether the task execution failed.
   *
   * @return bool
   *   TRUE if the task execution failed; FALSE otherwise.
   */
  public function isFailure() {
    $result = FALSE;
    $failure_states = array(
      self::ERROR,
      self::FAILED,
      self::KILLED,
      self::ERROR_ACKNOWLEDGED,
    );
    if (in_array($this->getState(), $failure_states)) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Indicates whether the task is still running.
   *
   * @return bool
   *   TRUE if the task is still running; FALSE otherwise.
   */
  public function isRunning() {
    $result = FALSE;
    $completed = $this->getCompleted();
    if (empty($completed)) {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Indicates whether the hosting task server has started processing this task.
   *
   * @return bool
   *   TRUE if the task has been started; FALSE otherwise.
   */
  public function hasStarted() {
    $result = TRUE;
    $started = $this->getStarted();
    if (empty($started)) {
      $result = FALSE;
    }
    return $result;
  }

  /**
   * Indicates whether the task server has finished processing this task.
   *
   * @return bool
   *   TRUE if the task has been completed; FALSE otherwise.
   */
  public function hasCompleted() {
    $result = TRUE;
    $completed = $this->getCompleted();
    if (empty($completed)) {
      $result = FALSE;
    }
    return $result;
  }

  /**
   * Forces this task into a failure state.
   *
   * This might be done because a particular process is taking way longer than
   * expected, and can be used to prevent a Wip object that is monitoring a
   * hosting task from spinning forever.
   */
  public function forceFail() {
    $this->setState(self::KILLED);
    if ($this->hasStarted()) {
      if ($this->hasCompleted()) {
        $time = $this->getCompleted() - $this->getStarted();
      } else {
        $time = time() - $this->getStarted();
      }
      $this->logs[] = sprintf("\nForce failed the task after running %s seconds", $time);
    } else {
      $time = time() - $this->getCreated();
      $this->logs[] = sprintf("\nForce failed the task after waiting %s seconds for it to start", $time);
    }
    if (!$this->hasCompleted()) {
      $this->setCompleted(time());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $result = array(
      'id' => $this->id,
      'queue' => $this->queue,
      'state' => $this->state,
      'description' => $this->description,
      'created' => $this->created,
      'started' => $this->started,
      'completed' => $this->completed,
      'sender' => $this->sender,
      'result' => $this->result,
      'cookie' => $this->cookies,
      'logs' => $this->logs,
    );
    return (object) $result;
  }

}
