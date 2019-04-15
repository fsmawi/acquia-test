<?php

namespace Acquia\Wip\Ssh;

use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\WipLogInterface;

/**
 * Convenience class that generates Ssh instances for git operations.
 */
class GitCommands {

  /**
   * The server where the workspace will be manipulated.
   *
   * @var string
   */
  private $environment;

  /**
   * The directory in which the workspace will be manipulated.
   *
   * @var string
   */
  private $workspace;

  /**
   * The git executable to invoke for all commands.
   *
   * @var string
   */
  private $git = '\git';

  /**
   * The ID of the Wip object.
   *
   * @var int
   */
  private $wipId;

  /**
   * The WipLog instance to use.
   *
   * @var WipLogInterface
   */
  private $wipLog;

  /**
   * The template used to derive the git wrapper script path.
   *
   * @var string
   */
  private $wrapperPathTemplate = '/home/%s/git_workspace_wrapper';

  /**
   * Indicates whether the git wrapper should be used to apply an ssh key.
   *
   * @var bool
   */
  private $useWrapper;

  /**
   * The SSH service through which commands will be executed.
   *
   * @var SshServiceInterface
   */
  private $sshService;

  /**
   * Creates a new instance of GitCommands.
   *
   * @param EnvironmentInterface $environment
   *   The environment on which the command will be executed.
   * @param string $workspace
   *   The git workspace directory; the path to the repo on the filesystem.
   * @param int $wip_id
   *   The ID of the WIP object this command is being executed by.
   * @param WipLogInterface $wip_log
   *   An instance of WipLogInterface for logging to the WIP log.
   * @param SshServiceInterface $ssh_service
   *   The SSH service object through which commands will be executed.
   * @param bool $use_wrapper
   *   If TRUE, the git wrapper will be used to apply the correct SSH key.
   */
  public function __construct(
    EnvironmentInterface $environment,
    $workspace,
    $wip_id,
    WipLogInterface $wip_log,
    SshServiceInterface $ssh_service = NULL,
    $use_wrapper = TRUE
  ) {
    $this->environment = $environment;
    $this->workspace = $workspace;
    $this->wipId = $wip_id;
    $this->wipLog = $wip_log;
    $this->useWrapper = $use_wrapper;
    if ($ssh_service !== NULL) {
      $this->sshService = $ssh_service;
    } else {
      $this->sshService = new SshService();
    }
  }

  /**
   * Sets the executable that will be called for all git commands.
   *
   * It is normally unnecessary to call this - it defaults to the git command
   * line tool.
   *
   * @param string $git
   *   The executable to invoke for all git commands.
   */
  public function setGitExecutable($git) {
    $this->git = $git;
  }

  /**
   * Returns the fully qualified server name where git operations are done.
   *
   * @return string
   *   The server name.
   */
  protected function getServer() {
    return $this->environment->getCurrentServer();
  }

  /**
   * Gets the absolute path to the git wrapper that uses the right SSH key file.
   *
   * @return string
   *   The absolute path to the wrapper script.
   */
  protected function getWrapperPath() {
    $environment = $this->getEnvironment();
    return sprintf($this->getWrapperPathTemplate(), $environment->getSitegroup());
  }

  /**
   * Gets the git wrapper script path template.
   *
   * @return string
   *   The template string.
   */
  public function getWrapperPathTemplate() {
    return $this->wrapperPathTemplate;
  }

  /**
   * Sets the git wrapper script path template.
   *
   * @param string $wrapper_path_template
   *   The template string e.g. "/home/%s/git_theme_wrapper".
   */
  public function setWrapperPathTemplate($wrapper_path_template) {
    $this->wrapperPathTemplate = $wrapper_path_template;
  }

  /**
   * Gets the executable that should be invoked for all git commands.
   *
   * @return string
   *   The executable if it has been set. Otherwise, NULL will be returned.
   */
  protected function getGitExecutable() {
    return $this->git;
  }

  /**
   * Gets the WIP object ID.
   *
   * @return int
   *   The Wip ID.
   */
  protected function getWipId() {
    return $this->wipId;
  }

  /**
   * Gets the WipLog instance.
   *
   * @return WipLog
   *   The WipLog instance.
   */
  protected function getWipLog() {
    return $this->wipLog;
  }

  /**
   * Gets the environment in which the git command will run.
   *
   * @return EnvironmentInterface
   *   The environment.
   */
  protected function getEnvironment() {
    return $this->environment;
  }

  /**
   * Gets the directory of the workspace.
   *
   * @return string
   *   The absolute path to the workspace directory.
   */
  protected function getWorkspaceDirectory() {
    return $this->workspace;
  }

  /**
   * Sets the directory of the workspace.
   *
   * @param string $directory
   *   The absolute path of the workspace directory.
   */
  public function setWorkspaceDirectory($directory) {
    $this->workspace = $directory;
  }

  /**
   * Creates a new Ssh instance for executing git commands.
   *
   * This is used any time a new Ssh instance is required, and saves the hassle
   * of specifying all of the parameters that are necessary when using Ssh
   * outside of the Wip context.
   *
   * @param string $description
   *   The human-readable description of the SSH command.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute a git command.
   */
  protected function createGitSsh($description) {
    $ssh = new Ssh();
    $this->sshService->setEnvironment($this->environment);
    $ssh->setSshService($this->sshService);
    $ssh->initialize($this->environment, $description, $this->getWipLog(), $this->getWipId());
    return $ssh;
  }

  /**
   * Returns a fully-formed git command.
   *
   * @param string $command
   *   The git operation and options that will be passed to the git executable
   *   e.g. "status".
   *
   * @return string
   *   The fully-formed git command string that can be executed from any
   *   directory on the remote server.
   */
  protected function renderGitCommand($command) {
    $git = $this->getGitExecutable();
    $dir = escapeshellarg($this->getWorkspaceDirectory());
    $username = $this->environment->getSitegroup();

    if ($this->useWrapper) {
      $path = $this->getWrapperPath();
      $git_command = sprintf('cd %s; HOME=/home/%s GIT_SSH=%s %s %s', $dir, $username, $path, $git, $command);
    } else {
      $git_command = sprintf('cd %s; HOME=/home/%s %s %s', $dir, $username, $git, $command);
    }

    return $git_command;
  }

  /**
   * Sets configuration values.
   *
   * @param string $name
   *   The name of the configuration setting e.g. "user.name".
   * @param string $value
   *   The value of the configuration setting.
   * @param bool $global
   *   Optional. Whether the configuration should be set globally.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function config($name, $value, $global = FALSE, $description = NULL) {
    if ($global) {
      $command = sprintf('config --global %s %s', $name, $value);
    } else {
      $command = sprintf('config %s %s', $name, $value);
    }
    $description = $description ?: sprintf('Setting configuration: git %s', $command);
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that can be used to clone a workspace.
   *
   * @param string $vcs_url
   *   The URL of the git repository.
   * @param string $vcs_path
   *   Optional. The VCS path. The clone of the repository will have this path
   *   checked out upon completion.
   * @param string $options
   *   Optional. The options that are passed to the clone command.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function cloneWorkspace($vcs_url, $vcs_path = '', $options = '', $description = NULL) {
    $dir = $this->getWorkspaceDirectory();
    if ($description === NULL) {
      $description = sprintf('Clone git repository %s into %s', $vcs_url, $dir);
    }
    if (!empty($vcs_path)) {
      $options .= sprintf(' --branch %s', $vcs_path);
    }
    $command = sprintf(
      'clone %s %s %s',
      $options,
      escapeshellarg($vcs_url),
      escapeshellarg($dir)
    );
    $ssh = $this->createGitSsh($description);
    // Note that the target directory will not exist before cloning; the command
    // must be executed from a different directory.
    $this->setWorkspaceDirectory('/');
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an SSH instance that can be used to add the specified remote.
   *
   * @param string $name
   *   The name of the remote to add.
   * @param string $url
   *   The URL for the new remote.
   * @param string $description
   *   Optional. The description associated with the SSH instance.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function addRemote($name, $url, $description = NULL) {
    if (empty($name) || !is_string($name)) {
      throw new \InvalidArgumentException('The "name" parameter must be a non-empty string.');
    }
    if (empty($url) || !is_string($url)) {
      throw new \InvalidArgumentException('The "url" parameter must be a non-empty string.');
    }
    if ($description === NULL) {
      $description = sprintf('Add "%s" as a remote named "%s".', $url, $name);
    }
    $command = sprintf('remote add %s %s', escapeshellarg($name), escapeshellarg($url));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an SSH instance that can be used to remove the specified remote.
   *
   * @param string $name
   *   The name of the remote to remove.
   * @param string $description
   *   Optional. The description associated with the SSH instance.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function removeRemote($name, $description = NULL) {
    if (empty($name) || !is_string($name)) {
      throw new \InvalidArgumentException('The "name" parameter must be a non-empty string.');
    }
    if ($description === NULL) {
      $description = sprintf('Remove "%s" as a remote.', $name);
    }
    $command = sprintf('remote remove %s', escapeshellarg($name));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * List the remotes.
   *
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function listRemotes($description = NULL) {
    $description = $description ?: 'List remotes';
    $command = 'remote -v';
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that lists all branches.
   *
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function listBranches($description = NULL) {
    $description = $description ?: 'List all branches';
    $command = 'branch -l';
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Checks out the specified branch.
   *
   * @param string $branch
   *   The branch to check out.
   * @param string $options
   *   Optional. Any options for the git checkout command.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function checkout($branch, $options = '', $description = NULL) {
    $description = $description ?: sprintf('Check out branch %s', $branch);
    $command = sprintf('checkout %s %s', $options, escapeshellarg($branch));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Gets the status of the workspace.
   *
   * @param string $options
   *   Optional. Options passed to git status.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function status($options = '', $description = NULL) {
    $description = $description ?: 'Get the workspace status';
    $command = sprintf('status %s', $options);
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Fetches changes from the specified source.
   *
   * @param string $remote
   *   Optional. If provided, fetch only from the specified remote. Otherwise,
   *   fetch from all available remotes.
   * @param string $vcs_path
   *   Optional. A the VCS path to fetch.
   * @param string $options
   *   Optional. Options to pass to the fetch command.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function fetch($remote = '--all', $vcs_path = '', $options = '', $description = NULL) {
    $description = $description ?: sprintf('Fetch %s', $remote);
    $command = sprintf('fetch %s %s', $options, escapeshellarg($remote));
    if (!empty($vcs_path)) {
      $command .= ' ' . escapeshellarg($vcs_path);
    }
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that can commit to the workspace.
   *
   * @param string $message
   *   The message associated with the commit.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function commit($message, $description = NULL) {
    $description = $description ?: 'Commit all repo changes';
    $command = sprintf('commit --allow-empty -a -m %s', escapeshellarg($message));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance to amend a commit.
   *
   * @param string $message
   *   The new message associated with the commit.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function amend($message, $description = NULL) {
    if ($description === NULL) {
      $description = $description ?: 'Amend a commit';
    }
    $command = sprintf('commit --amend -m %s', escapeshellarg($message));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that will push to a remote.
   *
   * @param string $branch
   *   The branch name to push to the specified remote.
   * @param string $remote
   *   Optional. The name of the remote to push the branch to. If not provided,
   *   the command will push to "origin".
   * @param bool $force
   *   Optional. If TRUE, a force push will be performed.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function push($branch, $remote = 'origin', $force = FALSE, $description = NULL) {
    if ($force) {
      $force = '--force';
      $description = $description ?: sprintf('Force push branch %s to remote %s', $branch, $remote);
    } else {
      $force = '';
      $description = $description ?: sprintf('Push branch %s to remote %s', $branch, $remote);
    }
    $command = sprintf('push %s %s %s', $force, escapeshellarg($remote), escapeshellarg($branch));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that will pull from a remote.
   *
   * @param string $branch
   *   The branch name to pull from the specified remote.
   * @param string $remote
   *   Optional. The name of the remote to pull the branch from. If not
   *   provided, the command will pull from "origin".
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function pull($branch, $remote = 'origin', $description = NULL) {
    $description = $description ?: sprintf('Pull branch %s from remote %s', $branch, $remote);
    $command = sprintf('pull %s %s', escapeshellarg($remote), escapeshellarg($branch));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that will create a branch.
   *
   * @param string $new_branch
   *   The branch name to create.
   * @param string $old_branch
   *   Optional. If provided, creates the new branch from this branch.
   *   Otherwise, the new branch will be created from the current branch.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function branch($new_branch, $old_branch = NULL, $description = NULL) {
    $description = $description ?: sprintf('Create branch %s', $new_branch);
    $old_branch = empty($old_branch) ? '' : escapeshellarg($old_branch);
    $command = sprintf('branch %s %s', $old_branch, escapeshellarg($new_branch));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that adds files to the index.
   *
   * @param string[] $files
   *   Files and/or directories to add to the index.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function add($files, $description = NULL) {
    if (!is_array($files)) {
      $files = array($files);
    }
    $description = $description ?: sprintf('Stage the following files: %s', implode(', ', $files));
    $command = 'add';
    foreach ($files as $file) {
      $command .= ' ' . escapeshellarg($file);
    }
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that adds all file changes to the index.
   *
   * @param string $dir
   *   Optional. The directory under which all file changes will be added.
   *   Defaults to ".", which means everything within the current directory.
   * @param bool $force
   *   Optional. Allow adding otherwise ignored files.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function addAll($dir = '.', $force = FALSE, $description = NULL) {
    if ($force) {
      $description = $description ?: sprintf('Force stage all files in %s', $dir);
    } else {
      $description = $description ?: sprintf('Stage all files in %s', $dir);
    }
    $options = array(
      '--all',
    );
    if ($force) {
      $options[] = '--force';
    }
    $command = sprintf('add %s %s', implode(' ', $options), escapeshellarg($dir));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that removes files.
   *
   * @param string[] $files
   *   Files and/or directories to remove.
   * @param string $options
   *   Optional. Any options to pass to git rm.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function rm($files, $options = '', $description = NULL) {
    if (!is_array($files)) {
      $files = array($files);
    }
    $description = $description ?: sprintf('Remove the following files: %s', implode(', ', $files));
    $command = sprintf('rm %s', $options);
    foreach ($files as $file) {
      $command .= ' ' . escapeshellarg($file);
    }
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that moves file contents.
   *
   * @param string $source
   *   The source file that will be moved.
   * @param string $destination
   *   The destination for the file.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function mv($source, $destination, $description = NULL) {
    $description = $description ?: sprintf('Move file %s to %s', $source, $destination);
    $command = sprintf('mv %s %s', escapeshellarg($source), escapeshellarg($destination));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that reverts changed files.
   *
   * @param string $path
   *   Optional. If provided, only the specified path will be reset.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function revert($path = NULL, $description = NULL) {
    $description = $description ?: 'Revert changed files';
    $command = sprintf('reset --hard HEAD %s', empty($path) ? '' : escapeshellarg($path));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Checks out the specified branch without performing a merge.
   *
   * @param string $branch
   *   The branch to check out.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function forceCheckoutBranch($branch, $description = NULL) {
    $description = $description ?: sprintf('Force checkout branch "%s"', $branch);
    $command = sprintf('checkout -B %s origin/%s', escapeshellarg($branch), escapeshellarg($branch));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Checks out the specified tag.
   *
   * @param string $tag
   *   The tag to check out.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function forceCheckoutTag($tag, $description = NULL) {
    $tag = self::removeTagsPrefix($tag);
    $branch = self::getBranchNameFromTag($tag);
    $command = sprintf(
      'checkout tags/%s -B %s',
      escapeshellarg($tag),
      escapeshellarg($branch)
    );
    $description = $description ?: sprintf('Force checkout tag "%s" as branch %s', $tag, $branch);
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Creates the specified branch.
   *
   * @param string $branch
   *   The branch to create.
   * @param mixed $options
   *   Optional. Options that will be passed to the branch create call. This
   *   parameter can be a string or an array.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function createBranch($branch, $options = '-b', $description = NULL) {
    $description = $description ?: sprintf('Create branch %s', $branch);
    if (is_array($options)) {
      $options = implode(' ', $options);
    } elseif (!is_string($options)) {
      throw new \InvalidArgumentException('The "options" parameter must be a string or an array of strings.');
    }

    $command = sprintf('checkout %s %s', $options, escapeshellarg($branch));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that soft-resets to a given ref.
   *
   * @param string $ref
   *   A git ref indicating a point in the history to reset the branch.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function reset($ref, $description = NULL) {
    $description = $description ?: sprintf('Soft-reset HEAD to %s', $ref);
    $command = sprintf('reset --soft %s', escapeshellarg($ref));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that removes untracked files.
   *
   * @param string $path
   *   Optional. If provided, only the specified path will be cleaned.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function clean($path = NULL, $description = NULL) {
    $description = $description ?: 'Remove untracked files';
    $command = sprintf('clean -f -d %s', empty($path) ? '' : escapeshellarg($path));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that cleans up the local repository.
   *
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function garbageCollect($description = NULL) {
    $description = $description ?: 'Cleanup unnecessary files and optimize the local repository';
    $command = 'gc --prune=now';
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that creates a tag.
   *
   * @param string $tag
   *   The name of the tag.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function tag($tag, $description = NULL) {
    $description = $description ?: sprintf('Create tag %s', $tag);
    $command = sprintf('tag %s', escapeshellarg($tag));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that deletes a tag.
   *
   * @param string $tag
   *   The name of the tag.
   * @param string $remote
   *   Optional. Specifies the remote from which the tag should be deleted.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function deleteTag($tag, $remote = 'origin', $description = NULL) {
    $description = $description ?: sprintf('Delete tag %s', $tag);
    $command = sprintf('push %s :%s', escapeshellarg($remote), escapeshellarg($tag));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that deletes a tag locally.
   *
   * @param string $tag
   *   The name of the tag.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function deleteLocalTag($tag, $description = NULL) {
    $description = $description ?: sprintf('Delete tag %s', $tag);
    $command = sprintf('tag -d %s', escapeshellarg($tag));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Validate that the specified tag exists.
   *
   * @param string $tag
   *   The name of the tag to validate.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function validateTag($tag, $description = NULL) {
    $description = $description ?: sprintf('Validate tag %s', $tag);
    $command = sprintf('ls-remote --tags| grep -e %s', escapeshellarg(sprintf('^.*/tags/%s$', $tag)));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Pushes the specified tag.
   *
   * @param string $tag
   *   The name of the tag to push.
   * @param string $remote
   *   Optional. The name of the remote to push the tag to. If not provided, the
   *   command will push to "origin".
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function pushTag($tag, $remote = NULL, $description = NULL) {
    if ($remote === NULL) {
      $remote = 'origin';
    }
    $description = $description ?: sprintf('Pushing tag %s to %s', $tag, $remote);
    $command = sprintf('push %s %s', $remote, $tag);
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Validate that the specified branch exists.
   *
   * @param string $branch
   *   The name of the branch to validate.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function validateBranch($branch, $description = NULL) {
    $description = $description ?: sprintf('Validate branch %s', $branch);
    $command = sprintf('ls-remote --heads| grep -e %s', escapeshellarg(sprintf('^.*/heads/%s$', $branch)));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Validates the specified VCS path exists without requiring a workspace clone.
   *
   * @param string $vcs_uri
   *   The VCS URI.
   * @param string $vcs_path
   *   The VCS path. This can be a branch or a tag.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function validateVcsPathExists($vcs_uri, $vcs_path, $description = NULL) {
    $description = $description ?: sprintf('Validate VCS path %s', $vcs_path);
    // This command must exit with 0 only if the specified VCS path exists.
    $command = sprintf(
      'ls-remote --tags --heads %s|grep -e %s',
      escapeshellarg($vcs_uri),
      escapeshellarg(sprintf('/%s$', $vcs_path))
    );
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Verifies the specified VCS URI is valid and pull access is granted.
   *
   * @param string $vcs_uri
   *   The VCS URI of the git repository.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function validateVcsUriExists($vcs_uri, $description = NULL) {
    $description = $description ?: sprintf('Validate pull access to git repository "%s".', $vcs_uri);
    $command = sprintf(
      'ls-remote %s > /dev/null',
      escapeshellarg($vcs_uri)
    );
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that deletes a branch.
   *
   * @param string $branch
   *   The name of the branch.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function deleteBranch($branch, $description = NULL) {
    $description = $description ?: sprintf('Delete branch %s', $branch);
    $command = sprintf('push :%s', escapeshellarg($branch));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that deletes a branch locally.
   *
   * @param string $branch
   *   The name of the branch.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function deleteLocalBranch($branch, $description = NULL) {
    $description = $description ?: sprintf('Delete branch %s', $branch);
    $command = sprintf('branch -D %s', escapeshellarg($branch));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Generates an Ssh instance that lists commit objects for a branch.
   *
   * By default git rev-list outputs commit objects in reverse chronological
   * order. We use the --reverse option here to retrieve the list in forward
   * chronological order, so that the earliest commit is the first item in the
   * list.
   *
   * @param string $branch
   *   The name of the branch.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function listRevisions($branch, $description = NULL) {
    $description = $description ?: sprintf('List revisions on %s', $branch);
    $command = sprintf('rev-list --reverse %s', escapeshellarg($branch));
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Gets the current reference.
   *
   * @param string $options
   *   Optional. Any options to pass to the command.
   * @param string $description
   *   Optional. The description of the command to be executed.
   *
   * @return SshInterface
   *   A new Ssh instance that can be used to execute the command.
   */
  public function getHeadRef($options = '', $description = NULL) {
    $description = $description ?: 'Get the reference of HEAD';
    $command = sprintf('rev-parse %s --verify HEAD', $options);
    $ssh = $this->createGitSsh($description);
    $ssh->setCommand($this->renderGitCommand($command));
    return $ssh;
  }

  /**
   * Checks whether a path contains the 'tags/' prefix.
   *
   * @param string $path
   *   The path to check.
   *
   * @return bool
   *   Whether the path contains the 'tags/' prefix.
   */
  public static function containsTagsPrefix($path) {
    return 1 === preg_match('#.*tags/.*#', $path);
  }

  /**
   * Removes the 'tags/' prefix from a path and returns the tag name.
   *
   * If the path does not start with the prefix, return the unmodified path.
   *
   * @param string $path
   *   The path.
   *
   * @return string
   *   The tag name without the 'tags/' prefix, or the original path if it did
   *   not contain the 'tags/' prefix.
   */
  public static function removeTagsPrefix($path) {
    if (1 === preg_match('%^tags/(.+)$%', $path, $match)) {
      return $match[1];
    }
    return $path;
  }

  /**
   * Returns the specified VCS path with no prefix.
   *
   * This removes tags/, heads/, etc.
   *
   * @param string $vcs_uri
   *   The VCS uri.
   * @param string $vcs_path
   *   The VCS path to strip.
   *
   * @return string
   *   The stripped VCS path.
   *
   * @throws \Exception
   *   Thrown when we cannot determine the correct vcs path without prefixes.
   */
  public function stripVcsPrefix($vcs_uri, $vcs_path) {
    $result = $vcs_path;
    $position = strrpos($result, '/');
    // If the string has / in it we need to run additional checks.
    if (FALSE !== $position) {
      $query = $this->validateVcsPathExists($vcs_uri, $vcs_path, 'Determining prefix')->exec();
      if ($query->isSuccess()) {
        $stdout = $query->getStdout();
        // Trim ref/heads and/or ref/tags from the remote. This lets us get the branch
        // without prefix even if its name includes / eg feature/MS-1234.
        foreach (['heads', 'tags'] as $element) {
          $check_string = "ref/$element/";
          $pos = strpos($stdout, $check_string);
          if ($pos !== FALSE) {
            $result = substr($stdout, strlen($check_string));
            break;
          }
        }
      } else {
        throw new \Exception(sprintf('Unable to determine the correct vcs path for %s:%s.', $vcs_uri, $vcs_path));
      }
    }
    return $result;
  }

  /**
   * Gets the name of the branch with which the tag should be checked out.
   *
   * Checking out a tag as a branch will avoid any potential issues that could
   * arise from being in a detached HEAD state.
   *
   * @param string $tag
   *   The tag name to get the branch for.
   *
   * @return string
   *   The branch name corresponding to the tag.
   */
  public static function getBranchNameFromTag($tag) {
    $tag = self::removeTagsPrefix($tag);
    $branch_name = sprintf('tags-%s', self::removeTagsPrefix($tag));
    return $branch_name;
  }

  /**
   * Gets the remote name corresponding to the specified URI.
   *
   * @param string $remotes
   *   The output of listRemotes.
   * @param string $git_uri
   *   The git URI.
   * @param mixed $modes
   *   Optional. If not specified both 'push' and 'fetch' modes will be
   *   searched. The remote name will only be returned if both the 'push' and
   *   'fetch' modes have the same remote name.
   *
   * @return string
   *   The name of the remote.
   */
  public function getRemoteName($remotes, $git_uri, $modes = NULL) {
    if (NULL === $modes) {
      $modes = array('push', 'fetch');
    } elseif (!is_array($modes)) {
      $modes = array(strval($modes));
    }
    $result = NULL;
    foreach ($modes as $mode) {
      $pattern = sprintf(
        '/([^\s]+)\s+%s\s+\(%s\)/s',
        preg_quote($git_uri, '/'),
        preg_quote($mode, '/')
      );
      if (preg_match(
        $pattern,
        $remotes,
        $matches
      ) === 1) {
        if (empty($result)) {
          $result = $matches[1];
        } else {
          if ($matches[1] !== $result) {
            return NULL;
          }
        }
      } else {
        return NULL;
      }
    }
    return $result;
  }

}
