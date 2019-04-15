<?php

namespace Acquia\Wip\Ssh;

/**
 * Imitates an SshService that executes all passed commands locally.
 */
class LocalExecSshService extends SshService implements SshServiceInterface {

  /**
   * Executes a command locally.
   *
   * Note: workloads will be executed on the local machine, regardless of any
   * parameters that have been set, such as the Environment.
   *
   * @param string $command
   *   The command to be run.
   * @param object $data
   *   Arbitrary data that is being passed in the command.  Note: it is not
   *   usually the responsibility of SshService to do anything with this data
   *   (like, say, json_encode()ing it and adding to the command).  Rather, the
   *   caller will have already set this data encoded in the command string, and
   *   is providing it to the SshService in un-encoded form in case SshService
   *   needs to alter its behaviour accordingly.
   *
   * @return SshResult
   *   The stdout and stderr from the command will be returned in the SshResult
   *   object, along with the exit code of the process.
   */
  public function exec($command, $data = NULL) {
    $start_time = !empty($data->startTime) ? $data->startTime : time();

    $descriptorspec = array(
      0 => array("pipe", "r"), // Stdin.
      1 => array("pipe", "w"), // Stdout.
      2 => array("pipe", "w"), // Stderr.
    );
    $proc = proc_open($command, $descriptorspec, $pipes);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    fclose($pipes[0]);
    $return = proc_close($proc);

    $result = new SshResult($return, $stdout, $stderr);
    $result->setEndTime(time());
    $result->setSuccessExitCodes($this->getSuccessExitCodes());
    $result->setStartTime($start_time);
    $result->setSecure($this->isSecure());
    if ($this->getEnvironment()) {
      $result->setEnvironment($this->getEnvironment());
    }
    $interpreter = $this->getResultInterpreter();
    if (!empty($interpreter)) {
      $result->setResultInterpreter($interpreter);
    }

    return $result;
  }

}
