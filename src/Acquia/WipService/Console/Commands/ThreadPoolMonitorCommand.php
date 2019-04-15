<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipIntegrations\DoctrineORM\StateStore;
use Acquia\WipService\App;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\State\MonitorDaemonPause;
use Acquia\Wip\Storage\StateStoreInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * The lowest process ID that we consider might possibly be a user process.
 */
define('WIP_SYSTEM_PID_MIN', 50);

/**
 * Class ThreadPoolCommand.
 */
class ThreadPoolMonitorCommand extends WipConsoleCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
This command provides information about the current status of the daemon and allows it to be stopped or started.

wipctl monitor-daemon status - indicates if the daemon is running and provides its pid if it is.
wipctl monitor-daemon stop - stops the daemon if it is running.
wipctl monitor-daemon start - starts the daemon if one is not running.
EOT;
    $description = '[start|stop|status]: "start" starts the daemon if none is running; "stop" will stop the daemon. If the daemon is not running but has left a pid file, "stop" will remove the pid file. "status" reports status, but takes no action.';
    $this->setName('monitor-daemon')
      ->setDescription('Monitor the Wip thread pool daemon.')
      ->setHelp($help)
      ->addArgument('operation', InputArgument::REQUIRED, $description);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $state_store = $this->dependencyManager->getDependency('acquia.wip.storage.state');
    $pause = $state_store->get(
      MonitorDaemonPause::STATE_NAME,
      MonitorDaemonPause::$defaultValue
    );
    if ($pause === MonitorDaemonPause::ON) {
      return;
    }

    $output->writeln('Checking WIP thread pool daemon.');

    $pidfile = App::getApp()['config.global']['pidfile.threadpooldaemon'];

    if (is_readable($pidfile)) {
      $pid = (int) trim(file_get_contents($pidfile));

      // This command checks to make sure that a daemon is running on the pid
      // provided in the pid file.
      $command = sprintf("ps u -p %d | grep run-daemon", $pid);
      exec($command, $out, $exit_status);

      if ($pid > WIP_SYSTEM_PID_MIN && $exit_status == 0) {
        $output->writeln(sprintf('WIP thread pool daemon found using PID file %s; PID: %s', $pidfile, $pid));
        $output->writeln('WIP thread pool daemon is up.');
        if ($input->getArgument('operation') == 'stop') {
          $output->writeln('Stopping daemon process...');

          $descendants = $this->getDescendants(array($pid));

          exec(sprintf('kill -%d %d', SIGABRT, $pid));

          do {
            if (isset($alive)) {
              sleep(1);
            }
            $alive = FALSE;
            foreach ($descendants as $descendant) {
              if ($this->isRunning($descendant)) {
                $alive = TRUE;
                break;
              }
            }
          } while ($alive);
        }
      } else {
        // If the pid file does not correspond to a running daemon process, it
        // is probably stale.
        $output->writeln(
          sprintf(
            'WIP thread pool daemon does not appear to be running at PID %d. The PID file %s might be stale.',
            $pid,
            $pidfile
          )
        );
        // Remove the PID file and restart the daemon only if the command was not "status".
        if ($input->getArgument('operation') !== 'status') {
          $output->writeln(sprintf('Removing stale PID file...', $pid));
          if (!unlink($pidfile)) {
            $output->writeln(sprintf('Unable to remove the stale PID file %s.', $pidfile));
            return;
          };

          // Remove server and PID from the state store.
          /** @var StateStoreInterface $state_store */
          $state_store = $this->dependencyManager->getDependency('acquia.wip.storage.state');
          $state_store->delete(StateStore::ACTIVE_THREAD_NAME);
          $this->startMonitorDaemon($input, $output);
        }
      }
    } else {
      $output->writeln(sprintf('Unable to locate WIP thread pool daemon using PID file %s', $pidfile));

      // Make sure that there isn't a daemon process already running. The
      // brackets around "r" ensures that grep and the current status call do
      // not show up in the results.
      $command = "ps u | grep [r]un-daemon";
      exec($command, $out, $exit_status);

      if ($exit_status == 0) {
        // The PID should be in the second column of the first line of output.
        $pid = preg_split('/\s+/', $out[0])[1];
        if ($pid > WIP_SYSTEM_PID_MIN) {
          $output->writeln(sprintf('WIP thread pool daemon found at PID: %s', $pid));
          $output->writeln(sprintf('Creating a PID file at %s...', $pidfile));
          file_put_contents($pidfile, $pid);
        }
      } else {
        $this->startMonitorDaemon($input, $output);
      }
    }
  }

  /**
   * Starts the daemon and creates a pid file if a start command is given.
   *
   * @param InputInterface $input
   *   The InputInterface object.
   * @param OutputInterface $output
   *   The OutputInterface object.
   */
  private function startMonitorDaemon(InputInterface $input, OutputInterface $output) {
    if ($input->getArgument('operation') == 'start') {
      $output->writeln('Starting daemon process...');
      // @todo - start proc, report its pid if possible and ideally also the connection ID for locks

      $logfile = App::getApp()['config.global']['log.threadpooldaemon'];
      if (!file_exists($dir = dirname($logfile))) {
        if (!mkdir($dir, 0770, TRUE)) {
          throw new \RuntimeException(sprintf('Unable to create log directory %s', $dir));
        }
      }
      $command = sprintf(
        'nohup %s/bin/wipctl run-daemon >> %s 2>&1 & echo $!',
        $this->getAppDirectory(),
        $logfile
      );

      // Can't use symfony/process for now, as it stops the proc when the
      // object is destroyed.
      exec($command, $out, $return);
      $output->writeln(sprintf(
        'Started daemon process with PID %d',
        (int) reset($out)
      ));
    }
  }

  /**
   * Checks if monitor daemon is running.
   */
  private function isRunning($pid) {
    $process = new Process(sprintf('ps -p %d', (int) $pid));
    $process->run();

    return $process->getExitCode() === 0;

    // @todo Also check for recent log output. There should be a log entry newer
    // than time() - length of task-process run.
  }

  /**
   * Recursively obtains all descendent process IDs from a given parent.
   *
   * @param array $pids
   *   An array of parent pids for which to find
   *   children/grandchildren/../descendants.
   *
   * @return array
   *   A flat array of all descendant pids of the given process (including that
   *   process itself).
   */
  private function getDescendants(array $pids) {
    $children = $pids;
    foreach ($pids as $pid) {
      $output = array();
      exec(sprintf('ps -o pid= --ppid %d', (int) $pid), $output, $return);
      $child_pids = array_map('intval', array_map('trim', $output));
      $children = array_merge($children, $this->getDescendants($child_pids));
    }
    return $children;
  }

}
