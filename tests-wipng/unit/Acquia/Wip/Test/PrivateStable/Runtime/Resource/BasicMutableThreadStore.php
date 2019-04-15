<?php

namespace Acquia\Wip\Test\PrivateStable\Runtime\Resource;

use Acquia\Wip\Runtime\Thread;
use Acquia\Wip\Ssh\SshInterface;
use Acquia\Wip\Storage\BasicThreadStore;

/**
 * Overrides a ThreadStoreInterface implementation.
 *
 * Overriding the ThreadStoreInterface implementation allows the Ssh client to
 * be replaced in each Thread that is stored. This is mainly intended for using
 * a stub in the SSH client for testing.
 */
class BasicMutableThreadStore extends BasicThreadStore {

  /**
   * Missing summary.
   *
   * @var SshInterface
   */
  private $sshClient;

  /**
   * Missing summary.
   */
  public function __construct(SshInterface $ssh) {
    $this->sshClient = $ssh;
  }

  /**
   * Missing summary.
   */
  public function save(Thread $thread) {
    $thread->dependencyManager->swapDependency('acquia.wip.ssh.client', $this->sshClient);
    $thread->dependencyManager->swapDependency('acquia.wip.storage.thread', $this);
    return parent::save($thread);
  }

}
