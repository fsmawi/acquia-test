<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\DependencyManagerInterface;
use Acquia\Wip\Exception\DependencyTypeException;
use Acquia\Wip\PhpErrorLog;
use Acquia\Wip\Storage\WipLogStoreInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * A simple implementation of the WipLogInterface.
 */
class WipLog implements WipLogInterface, \Serializable, DependencyManagedInterface {

  /**
   * The WipFactory resource name.
   */
  const RESOURCE_NAME = 'acquia.wip.wiplog';

  /**
   * The storage.
   *
   * @var WipLogStoreInterface
   */
  protected $store = NULL;

  /**
   * The dependency manager.
   *
   * @var DependencyManager
   */
  protected $dependencyManager;

  /**
   * Initializes this instance with the specified log store.
   *
   * @param WipLogStoreInterface|null $store
   *   The log store.
   *
   * @throws DependencyTypeException
   *   If one or more dependencies are not satisfied.
   */
  public function __construct(WipLogStoreInterface $store = NULL) {
    if ($store === NULL) {
      $this->dependencyManager = new DependencyManager();
      $this->dependencyManager->addDependencies($this->getDependencies());
    } else {
      $this->store = $store;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.wiplogstore' => 'Acquia\Wip\Storage\WipLogStoreInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, $object_id = NULL, $user_readable = FALSE) {
    if (!WipLogLevel::isValid($level)) {
      throw new \InvalidArgumentException(sprintf('Invalid log level "%s".', $level));
    }
    if (!is_string($message) || empty($message)) {
      throw new \InvalidArgumentException(sprintf('The message argument must be a non-empty string.'));
    }
    if (!is_bool($user_readable)) {
      throw new \InvalidArgumentException(sprintf('The user readable argument must be a boolean.'));
    }
    $entry = new WipLogEntry($level, $message, $object_id, time(), NULL, '0', $user_readable);
    try {
      $this->getStore()->save($entry);
    } catch (\Exception $e) {
      // Failed to write the log to the log store. Try using a file-based log
      // store instead.
      $file_store = new PhpErrorLog();
      $file_store->log($entry);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function multiLog($object_id, $level, $message) {
    $log_messages = $this->getLogMessages(func_get_args());
    // Log the message at the lowest log level in the set (highest priority).
    $highest_log_level = WipLogLevel::TRACE;
    $full_message = '';
    // Order the log messages by level.
    $all_log_levels = array(
      WipLogLevel::FATAL,
      WipLogLevel::ERROR,
      WipLogLevel::ALERT,
      WipLogLevel::WARN,
      WipLogLevel::INFO,
      WipLogLevel::DEBUG,
      WipLogLevel::TRACE,
    );
    foreach ($all_log_levels as $level) {
      if (!empty($log_messages[$level])) {
        if ($level < $highest_log_level) {
          $highest_log_level = $level;
        }
        $full_message .= implode('  ', $log_messages[$level]) . '  ';
      }
    }
    return $this->log($highest_log_level, $full_message, $object_id);
  }

  /**
   * Validates and extracts log messages from the specified arguments.
   *
   * @param array $args
   *   The arguments passed in to the multiLog method.
   *
   * @return array
   *   An associative array with log levels as the key, and the messages
   *   associated with the level as the value.
   */
  private function getLogMessages($args) {
    $result = array();
    $levels_and_messages = array_slice($args, 1);
    // Check to see if there is a mismatch between the number of specified
    // levels and the number of specified messages.
    $element_count = count($levels_and_messages);
    if ($element_count % 2 !== 0) {
      // Mismatch of levels and messages.
      throw new \InvalidArgumentException('Must specify a log level with each message.');
    }
    for ($i = 0; $i < $element_count; $i += 2) {
      $level = $levels_and_messages[$i];
      if (WipLogLevel::isValid($level)) {
        $message = $levels_and_messages[$i + 1];
        if (!is_string($message) || empty($message)) {
          throw new \InvalidArgumentException(sprintf('The message argument must be a non-empty string.'));
        }
        if (empty($result[$level])) {
          $result[$level] = array();
        }
        $result[$level][] = trim($message);
      }
    }
    return $result;
  }

  /**
   * Returns the log store used to persist messages.
   *
   * @return WipLogStoreInterface
   *   The log store.
   */
  public function getStore() {
    if ($this->store === NULL) {
      $this->store = $this->dependencyManager->getDependency('acquia.wip.wiplogstore');
    }
    return $this->store;
  }

  /**
   * Prevent the WipLog object from being serialized.
   */
  public function serialize() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
  }

  /**
   * Gets the WipLog instance.
   *
   * @param DependencyManagerInterface $dependency_manager
   *   Optional. The DependencyManager instance.
   *
   * @return WipLogInterface
   *   The WipLogInterface instance.
   *
   * @throws \Acquia\Wip\Exception\DependencyMissingException
   *   If a DependencyManager instance is provided but the WipLog has not been
   *   set as a dependency.
   */
  public static function getWipLog(DependencyManagerInterface $dependency_manager = NULL) {
    $result = NULL;
    if (NULL !== $dependency_manager) {
      $result = $dependency_manager->getDependency(self::RESOURCE_NAME);
    } else {
      try {
        $result = WipFactory::getObject(self::RESOURCE_NAME);
      } catch (\Exception $e) {
        // Fall back to a new instance of WipLog.
        $result = new self();
      }
    }
    return $result;
  }

}
