<?php

namespace Acquia\Wip\Ssh;

/**
 * The SshResultInterpreterInterface describes processed ssh results.
 */
interface SshResultInterpreterInterface {

  /**
   * Sets the SshResult this instance will interpret.
   *
   * @param SshResultInterface $result
   *   The result.
   */
  public function setSshResult(SshResultInterface $result);

  /**
   * Gets the SshResult set into this instance.
   *
   * @return SshResultInterface $result
   *   The result.
   *
   * @throws \RuntimeException
   *   If the result has not been set yet.
   */
  public function getSshResult();

}
