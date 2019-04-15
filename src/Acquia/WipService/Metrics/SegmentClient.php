<?php

namespace Acquia\WipService\Metrics;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Defines a client for the Segment service.
 */
class SegmentClient implements DependencyManagedInterface {
  /**
   * Client options.
   *
   * @var array
   */
  private $options;

  /**
   * The wip logger.
   *
   * @var WipLogInterface
   */
  private $wipLog;

  /**
   * Dependency manager.
   *
   * @var DependencyManager
   */
  private $dependencyManager;

  /**
   * Creates a new instance of SegmentClient.
   *
   * @param array $options
   *   Segment client configuration options.
   */
  public function __construct(array $options) {
    $this->checkOptions($options);
    $this->options = $options;
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies($this->getDependencies());
    $wip_log = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    $this->wipLog = $wip_log;
    $error_handler = function ($errno, $errmsg) use ($wip_log) {
      $error_message = sprintf("Segment client runtime error: %s", $errmsg);
      $wip_log->log(WipLogLevel::ERROR, $error_message);
      // RunTimeException class will take only numeric or null for errno parameter.
      if (is_numeric($errno) || is_null($errno)) {
        throw new \RuntimeException($error_message, $errno);
      } else {
        $error_message = sprintf("Illegal error number was returned by segment service. error number type: %s.", gettype($errno));
        if (is_string($errno)) {
          $error_message = $error_message . " error number: " . $errno;
        }
        $wip_log->log(WipLogLevel::ERROR, $error_message);
        throw new \RuntimeException($error_message);
      }
    };
    $segment_options = [
      'error_handler' => $error_handler,
    ];
    if (isset($this->options['host'])) {
      $segment_options['host'] = $this->options['host'];
    }
    \Segment::init(
      $this->options['project_key'],
      $segment_options
    );
  }

  /**
   * Check client options array.
   *
   * @param array $options
   *   The client options to check.
   */
  private function checkOptions(array $options) {
    if (!isset($options['sandbox']) || !is_bool($options['sandbox'])) {
      throw new \InvalidArgumentException('The sandbox option must be set and must be a boolean.');
    }
    if (!isset($options['project_key']) || !is_string($options['project_key'])) {
      throw new \InvalidArgumentException('The project_key option must be set and must be a string.');
    }
    if (!isset($options['environment']) || !is_string($options['environment'])) {
      throw new \InvalidArgumentException('The environment option must be set and must be a string.');
    }
  }

  /**
   * Decorate message with additional values and redacting sensitive values.
   *
   * @param array $message
   *   The message to decorate.
   *
   * @return array
   *   The decorated message.
   */
  private function decorateMessage(array $message) {
    $message['context']['environment'] = $this->options['environment'];
    if (isset($message['properties']['options']['acquiaCloudCredentials']['key'])) {
      $message['properties']['options']['acquiaCloudCredentials']['key'] = '*****';
    }
    if (isset($message['properties']['options']['applicationPrivateKey'])) {
      $message['properties']['options']['applicationPrivateKey'] = '*****';
    }
    if (isset($message['properties']['options']['pipelineApiSecret'])) {
      $message['properties']['options']['pipelineApiSecret'] = '*****';
    }
    if (!empty($message['properties']['options']['environmentVariables'])) {
      foreach ($message['properties']['options']['environmentVariables'] as $key => $value) {
        $message['properties']['options']['environmentVariables'][$key] = '*****';
      }
    }
    if (!empty($message['properties']['options']['sourcePrivateKey'])) {
      $message['properties']['options']['sourcePrivateKey'] = '*****';
    }
    if (!empty($message['properties']['options']['deployPrivateKey'])) {
      $message['properties']['options']['deployPrivateKey'] = '*****';
    }
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return [
      'acquia.wip.wiplog' => '\Acquia\Wip\Implementation\WipLog',
    ];
  }

  /**
   * Flush the client.
   */
  public function flush() {
    \Segment::flush();
  }

  /**
   * Tracks a user action.
   *
   * @param array $message
   *   The metrics message.
   *
   * @return bool
   *   The result of the call.
   */
  public function track(array $message) {
    $safe_message = $this->decorateMessage($message);
    $this->wipLog->log(WipLogLevel::DEBUG, 'Segment track message: ' . var_export($safe_message, TRUE));
    if ($this->options['sandbox']) {
      return TRUE;
    }
    return \Segment::track($safe_message);
  }

  /**
   * Tags traits about the user.
   *
   * @param array $message
   *   The metrics message.
   *
   * @return bool
   *   The result of the call.
   */
  public function identify(array $message) {
    $safe_message = $this->decorateMessage($message);
    $this->wipLog->log(WipLogLevel::DEBUG, 'Segment identify message: ' . var_export($safe_message, TRUE));
    if ($this->options['sandbox']) {
      return TRUE;
    }
    return \Segment::identify($safe_message);
  }

  /**
   * Tags traits about the group.
   *
   * @param array $message
   *   The metrics message.
   *
   * @return bool
   *   The result of the call.
   */
  public function group(array $message) {
    $safe_message = $this->decorateMessage($message);
    $this->wipLog->log(WipLogLevel::DEBUG, 'Segment group message: ' . var_export($safe_message, TRUE));
    if ($this->options['sandbox']) {
      return TRUE;
    }
    return \Segment::group($safe_message);
  }

  /**
   * Tracks a page view.
   *
   * @param array $message
   *   The metrics message.
   *
   * @return bool
   *   The result of the call.
   */
  public function page(array $message) {
    $safe_message = $this->decorateMessage($message);
    $this->wipLog->log(WipLogLevel::DEBUG, 'Segment page message: ' . var_export($safe_message, TRUE));
    if ($this->options['sandbox']) {
      return TRUE;
    }
    return \Segment::page($safe_message);
  }

  /**
   * Tracks a screen view.
   *
   * @param array $message
   *   The metrics message.
   *
   * @return bool
   *   The result of the call.
   */
  public function screen(array $message) {
    $safe_message = $this->decorateMessage($message);
    $this->wipLog->log(WipLogLevel::DEBUG, 'Segment screen message: ' . var_export($safe_message, TRUE));
    if ($this->options['sandbox']) {
      return TRUE;
    }
    return \Segment::screen($safe_message);
  }

  /**
   * Aliases the user id from a temporary id to a permanent one.
   *
   * @param array $message
   *   The metrics message.
   *
   * @return bool
   *   The result of the call.
   */
  public function alias(array $message) {
    $safe_message = $this->decorateMessage($message);
    $this->wipLog->log(WipLogLevel::DEBUG, 'Segment alias message: ' . var_export($safe_message, TRUE));
    if ($this->options['sandbox']) {
      return TRUE;
    }
    return \Segment::alias($safe_message);
  }

}
