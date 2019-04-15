<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\WipResultInterface;

/**
 * The interface for all classes that reveal the result of an Ssh call.
 */
interface SshResultInterface extends WipResultInterface {

  /**
   * Gets stdout.
   *
   * @return string
   *   The stdout of the process.
   */
  public function getStdout();

  /**
   * Gets stdout or a suppressed message if the object is secure.
   *
   * @return string
   *   The stdout of the process.
   */
  public function getSecureStdout();

  /**
   * Gets stderr.
   *
   * @return string
   *   The stderr of the process.
   */
  public function getStderr();

  /**
   * Gets stderr or a suppressed message if the object is secure.
   *
   * @return string
   *   The stderr of the process.
   */
  public function getSecureStderr();

  /**
   * Returns an id that uniquely represents this result.
   *
   * This is used to quickly retrieve a particular result from an associative
   * array, for example, when a signal is received this value is used to
   * identify the associated process.
   *
   * @return string
   *   The unique id.
   */
  public function getUniqueId();

  /**
   * Produces an ID that uniquely represents a result.
   *
   * @param string $server
   *   The server name.
   * @param int $pid
   *   The process ID.
   * @param int $start_time
   *   The UNIX timestamp of the starting time of the process.
   *
   * @return string
   *   A unique ID built from the passed parameters.
   */
  public static function createUniqueId($server, $pid, $start_time);

  /**
   * Returns the result interpreter for this instance, if provided.
   *
   * @return SshResultInterpreterInterface
   *   The interpreter.
   */
  public function getResultInterpreter();

  /**
   * Sets the result interpreter for this instance.
   *
   * @param SshResultInterpreterInterface $interpreter
   *   The interpreter.
   */
  public function setResultInterpreter(SshResultInterpreterInterface $interpreter);

}
