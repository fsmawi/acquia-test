<?php

namespace Acquia\Wip;

/**
 * Bundles a message with a relevant log message that will be applied on exit.
 */
class ExitMessage implements ExitMessageInterface {

  private $exitMessage = NULL;

  private $logMessage = NULL;

  private $logLevel = WipLogLevel::INFO;

  /**
   * Initializes a new instance.
   *
   * @param string $exit_message
   *   The exit message.
   * @param int $log_level
   *   Optional. The log level.
   * @param string $log_message
   *   Optional. The exit log message. If not provided the exit_message value will also be used for the log.
   */
  public function __construct($exit_message, $log_level = WipLogLevel::INFO, $log_message = NULL) {
    $this->setExitMessage($exit_message);
    $this->setLogLevel($log_level);
    $this->setLogMessage($log_message);
  }

  /**
   * {@inheritdoc}
   */
  public function getExitMessage() {
    return $this->exitMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogMessage() {
    return $this->logMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogLevel() {
    return $this->logLevel;
  }

  /**
   * Sets the exit message.
   *
   * @param string $exit_message
   *   The exit message.
   */
  private function setExitMessage($exit_message) {
    if (!is_string($exit_message)) {
      throw new \InvalidArgumentException('The exit_message parameter must be a string.');
    }
    $this->exitMessage = $exit_message;
  }

  /**
   * Sets the log level.
   *
   * @param int $log_level
   *   The log level.
   */
  private function setLogLevel($log_level) {
    if (!WipLogLevel::isValid($log_level)) {
      throw new \InvalidArgumentException('The log_level parameter must identify a valid WipLogLevel.');
    }
    $this->logLevel = $log_level;
  }

  /**
   * Sets the log message.
   *
   * @param string $log_message
   *   The log message.
   */
  private function setLogMessage($log_message) {
    if (NULL === $log_message) {
      $this->logMessage = $this->getExitMessage();
    } elseif (!is_string($log_message)) {
      throw new \InvalidArgumentException('The log_message parameter must be a string.');
    } else {
      $this->logMessage = $log_message;
    }
  }

}
