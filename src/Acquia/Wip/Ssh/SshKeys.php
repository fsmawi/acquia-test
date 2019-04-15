<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\AcquiaCloud\AcquiaCloud;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\WipLogInterface;

/**
 * This class is responsible for managing and registering SSH keys.
 */
class SshKeys implements SshKeysInterface {

  /**
   * The directory path where keys will be stored.
   *
   * @var string
   */
  public static $basePath = NULL;

  /**
   * The relative key filepath from self::$basePath.
   *
   * @var string
   */
  private $relativeKeyPath;

  /**
   * Sets the relative key filepath from self::$basePath.
   *
   * @param string $key_path
   *   The relative key filepath.
   *
   * @see SshKeys->getPath()
   */
  public function setRelativeKeyPath($key_path) {
    $this->relativeKeyPath = $key_path;
  }

  /**
   * {@inheritdoc}
   */
  public function hasKey(EnvironmentInterface $environment) {
    $path = $this->getPath($environment);
    return file_exists($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivateKeyPath(EnvironmentInterface $environment) {
    return $this->getPath($environment);
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicKeyPath(EnvironmentInterface $environment) {
    return sprintf('%s.pub', $this->getPrivateKeyPath($environment));
  }

  /**
   * {@inheritdoc}
   */
  public function createKey(EnvironmentInterface $environment, $user = NULL, $comment = NULL) {
    if (!$this->hasKey($environment)) {
      $this->sshKeyGen($environment, $user, $comment);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKey(EnvironmentInterface $environment) {
    $command = sprintf(
      'rm -f %s %s',
      escapeshellarg($this->getPrivateKeyPath($environment)),
      escapeshellarg($this->getPublicKeyPath($environment))
    );

    exec($command);
  }

  /**
   * {@inheritdoc}
   */
  public function registerKey(
    EnvironmentInterface $environment,
    WipLogInterface $logger,
    $nickname = NULL,
    $shell_access = TRUE,
    $vcs_access = TRUE,
    $blacklist = array()
  ) {
    $nickname = $nickname ?: self::WIP_KEY_NAME;
    $public_key = file_get_contents($this->getPublicKeyPath($environment));

    $cloud_api = new AcquiaCloud($environment, $logger);
    return $cloud_api->addSshKey($nickname, $public_key, $shell_access, $vcs_access, $blacklist);
  }

  /**
   * {@inheritdoc}
   */
  public function deregisterKey(EnvironmentInterface $environment, WipLogInterface $logger, $ssh_key_id) {
    if (!is_int($ssh_key_id)) {
      throw new \InvalidArgumentException('The SSH key ID must be an integer.');
    }
    $cloud_api = new AcquiaCloud($environment, $logger);
    return $cloud_api->deleteSshKey($ssh_key_id);
  }

  /**
   * Returns the path to the key matching the specified environment.
   *
   * By default, key pairs will be generated using the following pattern:
   *
   * /mnt/files/{site}.{env}/nobackup/keys/{hash}/{site}/is_rsa_{env}
   * /mnt/files/{site}.{env}/nobackup/keys/{hash}/{site}/is_rsa_{env}.pub
   *
   * - site: The hosting sitegroup.
   * - env:  The hosting environment.
   * - hash: A 2-digit hash to avoid over-populating directories.
   *
   * By setting an alternate relative key path, one may influence the relative
   * filepath from the base path. Caution should be exercised when setting the
   * base path to a different value, which by default is the nobackup dir.
   *
   * Leaving base path as the default value and setting the relative filepath to
   * "my_key", would result in the following key paths:
   *
   * /mnt/files/{site}.{env}/nobackup/my_key
   * /mnt/files/{site}.{env}/nobackup/my_key.pub
   *
   * @param EnvironmentInterface $environment
   *   The EnvironmentInterface instance.
   *
   * @return string
   *   The file path to the key.
   */
  protected function getPath(EnvironmentInterface $environment) {
    $base_path = self::getBasePath();

    if (!empty($this->relativeKeyPath)) {
      $path = sprintf('%s/%s', $base_path, ltrim($this->relativeKeyPath, '/'));
    } else {
      $sitegroup = $environment->getSitegroup();
      $filename  = $this->getKeyFilename($environment);
      $domain    = $this->getDomainFromServer($environment->getCurrentServer());
      // Generate a 2-digit hash of the sitegroup and domain to avoid
      // over-populating directories.
      $hash = substr(sha1(sprintf('%s-%s', $sitegroup, $domain)), 0, 2);

      $path = sprintf('%s/keys/%s/%s/%s', $base_path, $hash, $sitegroup, $filename);
    }

    return $path;
  }

  /**
   * Returns the domain name from the fully qualified server name.
   *
   * This is used to create a consistent hash for a particular sitegroup and
   * environment that is used to federate a directory structure to avoid having
   * too many files / directories in a single directory.
   *
   * @param string $server
   *   The fully qualified server name.
   *
   * @return string
   *   The domain name.
   */
  protected function getDomainFromServer($server) {
    $result = $server;
    $components = explode('.', $server);

    if (count($components) > 1) {
      // Remove the server name.
      array_shift($components);

      // Glue it back together.
      $result = implode('.', $components);
    }
    return $result;
  }

  /**
   * Generates an SSH key as the sitegroup user.
   *
   * @param EnvironmentInterface $environment
   *   The Environment to create the SSH key for.
   * @param string $user
   *   Optional. The unix user who should own this key.
   * @param string $comment
   *   Optional. The comment to use for the SSH key.
   *
   * @throws \Exception
   *   If the key could not be generated or the wrapper script could not be
   *   written.
   */
  private function sshKeyGen(EnvironmentInterface $environment, $user = NULL, $comment = NULL) {
    $private_key_path = $this->getPrivateKeyPath($environment);

    // Make sure the files are not present.
    $this->deleteKey($environment);

    // Create the directory in which the keys will reside.
    $command = sprintf('mkdir -p %s 2>&1 > /dev/null', escapeshellarg(dirname($private_key_path)));
    exec($command, $output, $exit_code);
    if ($exit_code !== 0) {
      throw new \Exception(sprintf(
        'Could not create the parent directory for the SSH keys on %s, command: "%s"',
        $environment->getSitegroup(),
        $command
      ));
    }

    // Execute ssh-keygen to generate the keys.
    $options = array(
      '-q', // Silence ssh-keygen output.
      sprintf('-f %s', escapeshellarg($private_key_path)), // Key filepath.
      '-b 4096', // Key bit length.
      "-N ''", // No passphrase.
    );
    if ($comment !== NULL) {
      $options[] = sprintf('-C %s', escapeshellarg($comment)); // Key comment.
    }
    $command = sprintf('/usr/bin/ssh-keygen %s 2> /dev/null', implode(' ', $options));
    exec($command, $output, $exit_code);
    if ($exit_code !== 0) {
      throw new \Exception(sprintf(
        'Could not generate SSH key on %s, command: "%s"',
        $environment->getSitegroup(),
        $command
      ));
    }

    // Change the ownership of the key if requested.
    if (NULL !== $user) {
      $command = sprintf('chown %s %s*', escapeshellarg($user), escapeshellarg($private_key_path));
      exec($command, $output, $exit_code);
      if ($exit_code !== 0) {
        throw new \Exception(sprintf(
          'Could not change file ownership of the SSH private key on %s, command: "%s"',
          $environment->getSitegroup(),
          $command
        ));
      }
    }

    // Change file permissions of the private key.
    $command = sprintf('chmod 600 %s', escapeshellarg($private_key_path));
    exec($command, $output, $exit_code);
    if ($exit_code !== 0) {
      throw new \Exception(sprintf(
        'Could not change file permissions of the SSH private key on %s, command: "%s"',
        $environment->getSitegroup(),
        $command
      ));
    }
  }

  /**
   * Returns the name of the file that contains the SSH key.
   *
   * @param EnvironmentInterface $environment
   *   The Environment instance.
   *
   * @return string
   *   The filename.
   */
  protected function getKeyFilename(EnvironmentInterface $environment) {
    return sprintf('id_rsa_%s', $environment->getEnvironmentName());
  }

  /**
   * Returns the base path where the keys will be stored.
   *
   * @return string
   *   The base path.
   */
  public function getBasePath() {
    $result = self::$basePath;
    if (!isset($result)) {
      $this_environment = Environment::getRuntimeEnvironment();
      $result = sprintf(
        '/mnt/files/%s.%s/nobackup',
        $this_environment->getSitegroup(),
        $this_environment->getEnvironmentName()
      );
    }
    return $result;
  }

  /**
   * Sets the path where the keys will be stored.
   *
   * @param string $base_path
   *   The path.
   */
  public static function setBasePath($base_path) {
    self::$basePath = $base_path;
  }

}
