<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\AcquiaCloud\AcquiaCloudProcess;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\WipLogInterface;

/**
 * This interface describes how keys are managed for the SSH layer.
 */
interface SshKeysInterface {

  /**
   * The name by which the SSH key will be registered with Cloud.
   */
  const WIP_KEY_NAME = 'WIP';

  /**
   * Indicates whether the key for the specified environment already exists.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return bool
   *   TRUE if the key exists; FALSE otherwise.
   */
  public function hasKey(EnvironmentInterface $environment);

  /**
   * Gets the path to the private SSH key for the specified environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return string
   *   The path to the private key for the specified environment.
   */
  public function getPrivateKeyPath(EnvironmentInterface $environment);

  /**
   * Gets the path to the public SSH key for the specified environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   *
   * @return string
   *   The path to the public key for the specified environment.
   */
  public function getPublicKeyPath(EnvironmentInterface $environment);

  /**
   * Creates the SSH key for the specified environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   * @param string $user
   *   Optional. The user who should own the key.
   * @param string $comment
   *   Optional. The comment to use for the SSH key.
   */
  public function createKey(EnvironmentInterface $environment, $user = NULL, $comment = NULL);

  /**
   * Deletes the SSH key for the specified environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   */
  public function deleteKey(EnvironmentInterface $environment);

  /**
   * Registers the SSH key with Cloud.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   * @param WipLogInterface $logger
   *   The logger.
   * @param string $nickname
   *   The Hosting name associated with the key.
   * @param bool $shell_access
   *   Set to TRUE if the new key will have SSH access.
   * @param bool $vcs_access
   *   Set to TRUE if the new key will have access to the VCS repository.
   * @param string[] $blacklist
   *   A list of environments that the key will not have access to.
   *
   * @return AcquiaCloudProcess
   *   The process representing the hosting task responsible for registering the
   *   SSH key.
   */
  public function registerKey(
    EnvironmentInterface $environment,
    WipLogInterface $logger,
    $nickname = NULL,
    $shell_access = TRUE,
    $vcs_access = TRUE,
    $blacklist = array()
  );

  /**
   * Deregisters the SSH key with Cloud.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   * @param WipLogInterface $logger
   *   The logger.
   * @param int $ssh_key_id
   *   The id of the SSH key to remove.
   *
   * @return AcquiaCloudProcess
   *   The process representing the hosting task responsible for de-registering
   *   the SSH key.
   *
   * @throws \InvalidArgumentException
   *   If the ssh_key_id parameter is not an integer.
   */
  public function deregisterKey(EnvironmentInterface $environment, WipLogInterface $logger, $ssh_key_id);

}
