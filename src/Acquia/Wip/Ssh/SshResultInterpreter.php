<?php

namespace Acquia\Wip\Ssh;

/**
 * The SshResultInterpreter implements the SshResultInterpreterInterface.
 */
abstract class SshResultInterpreter implements SshResultInterpreterInterface {

  /**
   * The result to be interpreted.
   *
   * @var SshResultInterface
   */
  private $result;

  /**
   * {@inheritdoc}
   */
  public function setSshResult(SshResultInterface $result) {
    $this->result = $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSshResult() {
    if (empty($this->result)) {
      throw new \RuntimeException('The ssh result has not been set.');
    }
    return $this->result;
  }

}
