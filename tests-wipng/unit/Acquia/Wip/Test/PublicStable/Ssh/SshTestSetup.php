<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Implementation\SqliteWipLogStore;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Ssh\Ssh;
use Acquia\Wip\Ssh\SshKeys;
use Acquia\Wip\Ssh\SshService;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;

/**
 * Missing summary.
 */
class SshTestSetup {

  /**
   * Configures keys and an Environment instance for a local ssh test.
   *
   * @param bool $create_key
   *   If true, an Ssh key will be created.
   * @param EnvironmentInterface $env
   *   Optional. The EnvironmentInterface instance to configure.
   *
   * @return Environment
   *   The environment.
   */
  public static function setUpLocalSsh($create_key = TRUE, EnvironmentInterface $env = NULL) {
    Ssh::setSshWrapper(sprintf('%s/scripts/ssh_wrapper', getcwd()));
    Ssh::setGlobalExecOptions('--temp-dir /tmp');
    $username = posix_getpwuid(posix_geteuid())['name'];
    SshService::setTestUsername($username);
    Environment::setRuntimeSitegroup('sitegroup');
    Environment::setRuntimeEnvironmentName('prod');
    if (empty($env)) {
      $env = new Environment();
    }
    $env->setSitegroup(get_current_user());
    $env->setEnvironmentName('env');
    $env->setServers(array('localhost'));
    $env->selectNextServer();
    $env->setCloudCredentials(AcquiaCloudTestSetup::getCreds());
    $ssh_service = new SshService($env);

    if ($create_key) {
      // Create a key if required.
      SshKeys::setBasePath(sys_get_temp_dir());
      $keys = new SshKeys();
      if (!$keys->hasKey($env)) {
        $keys->createKey($env);
      }

      // Copy the key so the ssh will work. Start by backing up authorized_keys.
      $ssh_dir = '$HOME/.ssh';
      $authorized_keys = '$HOME/.ssh/authorized_keys';
      $authorized_keys_backup = '$HOME/.ssh/authorized_keys.bak';
      $public_key = $keys->getPublicKeyPath($env);

      $command = <<<EOT
if [ ! -f $authorized_keys ]; then
  mkdir $ssh_dir;
  chmod 700 $ssh_dir;
  touch $authorized_keys;
  chmod 600 $authorized_keys;
fi
if [ ! -f $authorized_keys_backup ]; then
  mv $authorized_keys $authorized_keys_backup;
fi;
cp $public_key $authorized_keys;
chmod og-r $authorized_keys;
EOT;
      exec($command);
    }
    return $env;
  }

  /**
   * Missing summary.
   */
  public static function clearLocalSsh() {
    // Remove the key from the authorized keys.
    $authorized_keys = '$HOME/.ssh/authorized_keys';
    $authorized_keys_backup = '$HOME/.ssh/authorized_keys.bak';

    $command = <<<EOT
if [ -f $authorized_keys_backup ]; then
  rm $authorized_keys;
  mv $authorized_keys_backup $authorized_keys;
fi
EOT;
    exec($command);
    Ssh::setSshWrapper();
    Ssh::setGlobalExecOptions();
    SshService::setTestUsername(NULL);
  }

  /**
   * Missing summary.
   */
  public static function createWipLog() {
    return new WipLog(new SqliteWipLogStore());
  }

}
