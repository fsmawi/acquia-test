<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;

/**
 * Constructs commands for common file system operations.
 *
 * Commands will be executed on the environment provided at instantiation time,
 * which could be local or remote.
 */
class SshFileCommands {

  use \Acquia\Wip\Security\SecureTrait;

  /**
   * The ID of the WIP object.
   *
   * @var int
   */
  private $wipId;

  /**
   * An instance of WipLogInterface for logging to the WIP log.
   *
   * @var WipLogInterface
   */
  private $wipLog;

  /**
   * The SSH service through which commands will be executed.
   *
   * @var SshServiceInterface
   */
  private $sshService;

  /**
   * The environment on which to execute commands.
   *
   * @var EnvironmentInterface
   */
  private $environment;

  /**
   * Creates a new instance of SshFileCommands.
   *
   * @param EnvironmentInterface $environment
   *   The environment on which to execute commands.
   * @param int $wip_id
   *   The ID of the Wip object associated with this instance.
   * @param WipLogInterface $wip_log
   *   The logger.
   * @param SshServiceInterface $ssh_service
   *   An instance of SshServiceInterface.
   */
  public function __construct(
    EnvironmentInterface $environment,
    $wip_id,
    WipLogInterface $wip_log,
    SshServiceInterface $ssh_service
  ) {
    $this->environment = $environment;
    $this->wipId = $wip_id;
    $this->wipLog = $wip_log;
    $this->sshService = $ssh_service;
    $ssh_keys = new SshKeys();
    $this->sshService->setKeyPath($ssh_keys->getPrivateKeyPath($environment));
  }

  /**
   * Gets the WIP object ID associated with the file command.
   *
   * @return int
   *   The WIP object ID.
   */
  public function getWipId() {
    return $this->wipId;
  }

  /**
   * Gets the logger.
   *
   * @return WipLogInterface
   *   The logger.
   */
  public function getWipLog() {
    return $this->wipLog;
  }

  /**
   * Creates a new Ssh instance for executing SSH commands.
   *
   * @param string $description
   *   The human-readable description of the SSH command.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute a file system command.
   */
  protected function createFileSsh($description) {
    $ssh = new Ssh();
    $this->sshService->setEnvironment($this->environment);
    $ssh->setSshService($this->sshService);
    $ssh->initialize($this->environment, $description, $this->getWipLog(), $this->getWipId());
    $ssh->setSecure($this->isSecure());
    return $ssh;
  }

  /**
   * Returns a Unix command name appropriate for the target system.
   *
   * Frequently, BSD versions of unix commands (which would be found on a Mac)
   * work differently from their Linux counterparts.  This function allows us to
   * override the default (Linux) versions where needed.  It is, for example,
   * possible to install GNU-compatible utilities on a Mac with a 'g' prefix
   * (homebrew will do this by default when running `brew install coreutils`)
   * and then override all the needed command keys to use commands with their
   * g-prefixed versions.  Refer to the test config file at
   * tests/Acquia/Wip/Test/factory.cfg for example overrides for Macs.
   *
   * @param string $default
   *   A default command to use if none is configured.  This should typically be
   *   the command that will work on Acquia hosting, and overrides can be
   *   supplied in a factory.cfg override file on other systems (in
   *   ~/.wip-service/config/config.factory.cfg).  For example, to override the
   *   "stat" command, set $acquia.wip.ssh.commands.stat in the factory.cfg
   *   override file.  The key in the configuration file will be derived from
   *   the default command name by stripping all characters other than
   *   alphanumeric, '-', and '_'.
   *
   * @return string
   *   The command name.
   */
  public function getUnixCall($default) {
    // Construct a key to use in the configuration file, only accepting
    // alphanumeric characters, '-' and '_' in the key.
    $key = preg_replace('/[^a-zA-Z0-9_-]/', '', $default);
    return WipFactory::getString("\$acquia.wip.ssh.commands.$key", $default);
  }

  /**
   * Checks if the specified file exists.
   *
   * @param string $path
   *   The path of the file or directory to check.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function exists($path, $description = NULL) {
    $description = $description ?: sprintf('Checking if file exists: %s', $path);
    return $this->getFilePermissions($path, $description);
  }

  /**
   * Gets the permissions of the specified file.
   *
   * @param string $path
   *   The file path.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function getFilePermissions($path, $description = NULL) {
    $description = $description ?: sprintf('Get permissions for %s', $path);
    $ssh = $this->createFileSsh($description);
    $format = escapeshellarg('%a');
    $path = escapeshellarg($path);
    $command = sprintf('%s -L --format %s %s', $this->getUnixCall('\stat'), $format, $path);
    $ssh->setCommand($command);
    $ssh->setResultInterpreter(new StatResultInterpreter($path, $this->getWipId()));
    return $ssh;
  }

  /**
   * Gets the owner of the specified file.
   *
   * @param string $path
   *   The file path.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function getFileOwner($path, $description = NULL) {
    $description = $description ?: sprintf('Get owner of %s', $path);
    $ssh = $this->createFileSsh($description);
    $format = escapeshellarg('%U');
    $path = escapeshellarg($path);
    $command = sprintf('%s -L --format %s %s', $this->getUnixCall('\stat'), $format, $path);
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Gets the group of the specified file.
   *
   * @param string $path
   *   The file path.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function getFileGroup($path, $description = NULL) {
    $description = $description ?: sprintf('Get group of %s', $path);
    $ssh = $this->createFileSsh($description);
    $format = escapeshellarg('%G');
    $path = escapeshellarg($path);
    $command = sprintf('%s -L --format %s %s', $this->getUnixCall('\stat'), $format, $path);
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Copies a file to a specified destination.
   *
   * @param string $source
   *   The filepath of the source file to copy.
   * @param string $destination
   *   The filepath of the destination.
   * @param bool $recursive
   *   Set to TRUE to copy directories.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function copy($source, $destination, $recursive = FALSE, $description = NULL) {
    $description = $description ?: sprintf('Copy file from %s to %s', $source, $destination);
    $ssh = $this->createFileSsh($description);
    $source = escapeshellarg($source);
    $destination = escapeshellarg($destination);
    if ($recursive) {
      $command = sprintf('\cp -r %s %s', $source, $destination);
    } else {
      $command = sprintf('\cp %s %s', $source, $destination);
    }
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Changes the file mode of the specified file.
   *
   * @param int $mode
   *   The file mode to set.
   * @param string $path
   *   The file path.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function chmod($mode, $path, $description = NULL) {
    $description = $description ?: sprintf('Change the file mode of %s to %o', $path, $mode);
    $ssh = $this->createFileSsh($description);
    $command = sprintf('\chmod %o %s', $mode, escapeshellarg($path));
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Changes the ownership of the specified file.
   *
   * @param string $user
   *   The user who should own the file(s).
   * @param string $path
   *   The path of the file(s).
   * @param string $group
   *   Optional. The group who should own the file(s).
   * @param string $recursive
   *   Optional. Whether or not the operation should be recursive.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function chown($user, $path, $group = FALSE, $recursive = FALSE, $description = NULL) {
    $owner = $group ? sprintf('%s:%s', $user, $group) : $user;
    $r_flag = $recursive ? '-R' : '';
    $description = $description ?: sprintf('Change the file mode of %s to %s', $path, $owner);
    $ssh = $this->createFileSsh($description);
    $command = sprintf('\chown %s %s %s', $r_flag, escapeshellarg($owner), escapeshellarg($path));
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Creates the specified directory.
   *
   * @param string $dir
   *   The full directory path to create.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function mkdir($dir, $description = NULL) {
    $description = $description ?: sprintf('Create the directory %s', $dir);
    $ssh = $this->createFileSsh($description);
    $command = sprintf('\mkdir -p %s', escapeshellarg($dir));
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Writes the specified file contents.
   *
   * @param string $file_contents
   *   The contents to write to the file.
   * @param string $destination
   *   The destination path.
   * @param bool $append
   *   Optional. Append contents to the file. Defaults to overwriting the file.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function writeFile($file_contents, $destination, $append = FALSE, $description = NULL) {
    $description = $description ?: sprintf('Write contents to the file %s', $destination);
    $ssh = $this->createFileSsh($description);
    $file_contents = base64_encode($file_contents);
    $destination = escapeshellarg($destination);
    $redirect = $append ? '>>' : '>';
    $command = sprintf(
      '%s -n %s | %s --decode %s %s && \echo "ok"',
      $this->getUnixCall('\echo'),
      $file_contents,
      $this->getUnixCall('\base64'),
      $redirect,
      $destination
    );
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Reads the contents of the specified file.
   *
   * @param string $path
   *   The file path.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function cat($path, $description = NULL) {
    $description = $description ?: sprintf('Read the contents of the file %s', $path);
    $ssh = $this->createFileSsh($description);
    $command = sprintf('\cat %s', escapeshellarg($path));
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Gets the md5 sum of a file.
   *
   * @param string $path
   *   The path of the file.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function getMd5Sum($path, $description = NULL) {
    $description = $description ?: sprintf('Get the md5sum of %s', $path);
    $ssh = $this->createFileSsh($description);
    // Construct the command such that only the md5sum is returned.
    $path = escapeshellarg($path);
    $command = sprintf('%s %s | %s -d" " -f1', $this->getUnixCall('\md5sum'), $path, $this->getUnixCall('\cut'));
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Ensures that the specified file exists.
   *
   * @param string $path
   *   The path of the file to ensure it exists.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function touch($path, $description = NULL) {
    $description = $description ?: sprintf('Touch file %s', $path);
    $ssh = $this->createFileSsh($description);
    $command = sprintf('\touch %s', escapeshellarg($path));
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Deletes the specified file.
   *
   * @param string $path
   *   The path of the file to unlink.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function unlink($path, $description = NULL) {
    $description = $description ?: sprintf('Unlink file %s', $path);
    $ssh = $this->createFileSsh($description);
    $command = sprintf('\unlink %s', escapeshellarg($path));
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Deletes the specified directory.
   *
   * @param string $path
   *   The path of the directory to delete.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function rmdir($path, $description = NULL) {
    $description = $description ?: sprintf('Remove directory %s', $path);
    $ssh = $this->createFileSsh($description);
    $command = sprintf('\rmdir %s', escapeshellarg($path));
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Deletes the specified file or directory.
   *
   * @param string $path
   *   The path of the file or directory to delete.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function forceRemove($path, $description = NULL) {
    $description = $description ?: sprintf('Force remove %s', $path);
    $ssh = $this->createFileSsh($description);
    $command = sprintf('\rm -rf %s', escapeshellarg($path));
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Creates a temporary directory.
   *
   * @param string $dir
   *   The directory in which the temporary directory should be created.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function makeTmpDir($dir, $description = NULL) {
    $description = $description ?: sprintf('Create a temporary directory in directory %s', $dir);
    $ssh = $this->createFileSsh($description);
    $command = sprintf('\mkdir %1$s; \mktemp -d -p %1$s', escapeshellarg($dir));
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Creates a new SSH key.
   *
   * @param string $private_key_path
   *   The full path and filename of the key to create.
   * @param string $comment
   *   Optional. The SSH key comment.
   * @param string $description
   *   Optional. The description of the resulting SSH command.  If not provided
   *   an appropriate description will be applied.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function createSshKey($private_key_path, $comment = NULL, $description = NULL) {
    $description = $description ?: sprintf('Create SSH key %s.', $private_key_path);
    $ssh = $this->createFileSsh($description);

    // Create the directory in which the keys will reside.
    $safe_private_key_dir = escapeshellarg(dirname($private_key_path));
    $safe_private_key_path = escapeshellarg($private_key_path);
    $commands = array();
    $commands[] = "mkdir -p {$safe_private_key_dir}";

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
    $commands[] = sprintf('/usr/bin/ssh-keygen %s', implode(' ', $options));

    // Change file permissions of the private key.
    $commands[] = sprintf('chmod 600 %s', $safe_private_key_path);

    $command = implode(' && ', $commands);
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Deletes the specified SSH key.
   *
   * @param string $private_key_path
   *   The full path and filename of the SSH key to delete.
   * @param string $description
   *   Optional. The description of the resulting SSH command.  If not provided
   *   an appropriate description will be applied.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function deleteSshKey($private_key_path, $description = NULL) {
    $description = $description ?: sprintf('Delete SSH key %s.', $private_key_path);
    $ssh = $this->createFileSsh($description);

    // Create the directory in which the keys will reside.
    $safe_private_key_path = escapeshellarg($private_key_path);
    $safe_public_key_path = escapeshellarg($private_key_path . '.pub');
    $commands = array();
    $commands[] = "chmod u+w {$safe_private_key_path} {$safe_public_key_path}";
    $commands[] = "rm -f {$safe_private_key_path} {$safe_public_key_path}";

    $command = implode(' && ', $commands);
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Securely copies a file from the current environment to the destination.
   *
   * @param EnvironmentInterface $destination_environment
   *   The environment to which the file should be copied.
   * @param string $source_file
   *   The absolute path of the file that will be copied.
   * @param string $destination_path
   *   The absolute path where the file will be copied to.
   * @param string[] $options
   *   Optional. Any options that should be used.
   * @param string $user
   *   Optional. The user name.
   * @param string $description
   *   Optional. The command description.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function scp(
    EnvironmentInterface $destination_environment,
    $source_file,
    $destination_path,
    $options = NULL,
    $user = NULL,
    $description = NULL
  ) {
    // Validate the arguments and initialize.
    $destination_server = $destination_environment->getCurrentServer();
    if (empty($destination_server)) {
      throw new \InvalidArgumentException('The "destination_environment" parameter must have a current server.');
    }
    if (!is_string($source_file)) {
      throw new \InvalidArgumentException('The "source_file" argument must be a string.');
    }
    if (!is_string($destination_path)) {
      throw new \InvalidArgumentException('The "destination_file" argument must be a string.');
    }
    if (!is_array($options)) {
      $options = array(
        '-p',
        '-o "StrictHostKeyChecking no"',
      );
    }
    if (NULL === $user) {
      $user = $destination_environment->getUser();
    }
    if (NULL === $description) {
      $description = sprintf(
        'Copy file %s using scp to %s.',
        $source_file,
        $destination_environment->getCurrentServer()
      );
    }

    // Construct the scp command.
    $ssh = $this->createFileSsh($description);
    $options[] = sprintf('-P %s', $destination_environment->getPort());
    $key_path = $destination_environment->getSshKeyPath();
    if (!empty($key_path)) {
      $options[] = sprintf('-i %s', escapeshellarg($key_path));
    }
    $destination = sprintf(
      '%s@%s:%s',
      $user,
      $destination_environment->getCurrentServer(),
      $destination_path
    );
    $command = sprintf(
      'scp %s %s %s',
      implode(' ', $options),
      escapeshellarg($source_file),
      escapeshellarg($destination)
    );
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Rsyncs from one directory to another.
   *
   * @param string $source
   *   The path of the source.
   * @param string $destination
   *   The path of the destination.
   * @param mixed $options
   *   Optional. Either a string or string array of options. A string array
   *   will be converted to a string by inserting a space between each option.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function rsync($source, $destination, $options = '-a', $description = NULL) {
    $description = $description ?: sprintf('Rsync directory "%s" to "%s".', $source, $destination);
    $ssh = $this->createFileSsh($description);
    if (is_array($options)) {
      // Convert the options to a string.
      $options = implode(' ', $options);
    }
    $command = sprintf(
      '\rsync %s %s %s',
      $options,
      escapeshellarg($source),
      escapeshellarg($destination)
    );
    $ssh->setCommand($command);
    return $ssh;
  }

  /**
   * Ensures there is a trailing directory separator.
   *
   * @param string $path
   *   The path.
   *
   * @return string
   *   The specified path with a single trailing slash.
   */
  public function ensureTrailingSeparator($path) {
    if (substr($path, -1) !== DIRECTORY_SEPARATOR) {
      $path = $path . DIRECTORY_SEPARATOR;
    }
    return $path;
  }

}
