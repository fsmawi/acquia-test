<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipIntegrations\DoctrineORM\ServerStore;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\AcquiaCloud\AcquiaCloud;
use Acquia\Wip\AcquiaCloud\CloudCredentials;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudServerInfo;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\Runtime\Server;
use Acquia\Wip\ServerStatus;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\WipLogLevel;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The server command edits and displays the server configuration.
 *
 * These servers are used to execute Wip tasks.
 */
class ServerCommand extends WipConsoleCommand {

  /**
   * The Wip pool storage instance.
   *
   * @var WipPoolStoreInterface
   */
  private $wipPoolStore;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $help = <<<EOT
This is an internal-use command for managing the server list.
EOT;

    $this->setName('server')
      ->setDescription('Edit and view servers.')
      ->setHelp($help)
      ->addArgument(
        'action',
        InputArgument::REQUIRED,
        'The action to perform on the server store (list, check, update).'
      )
      ->addOption(
        'activate-servers',
        'a',
        InputOption::VALUE_REQUIRED,
        'Activate the specified server(s) regardless of whether it is currently activated in Acquia Hosting.'
      )
      ->addOption(
        'format',
        'f',
        InputOption::VALUE_REQUIRED,
        'Identifies the output format (text, json).',
        'text'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $exit_code = 0;
    $this->wipPoolStore = $this->dependencyManager->getDependency('acquia.wip.storage.wippool');

    $action = $input->getArgument('action');
    $format = $input->getOption('format');

    if (!in_array($action, ['list', 'check', 'update'])) {
      $output->writeln(sprintf('Cannot complete command - unknown action %s', $action));
      $exit_code = 1;
    }

    if (in_array($action, ['check', 'update'])) {
      $update = strcmp($action, 'update') === 0;
      $activate_servers_string = $input->getOption('activate-servers');
      if (!empty($activate_servers_string)) {
        $activate_servers = explode(',', $activate_servers_string);
      } else {
        $activate_servers = array();
      }

      $result = $this->verifyServerTable($update, $activate_servers);
      switch ($format) {
        case 'json':
          $output->writeln(json_encode($result));
          break;

        default:
          if (!empty($result->addedServers)) {
            if ($update) {
              $output->writeln(sprintf("Added servers: %s", implode(', ', $result->addedServers)));
            } else {
              $output->writeln(sprintf("Servers that should be added: %s", implode(', ', $result->addedServers)));
            }
          }
          if (!empty($result->disabledServers)) {
            if ($update) {
              $output->writeln(sprintf("Disabled servers: %s", implode(', ', $result->disabledServers)));
            } else {
              $output->writeln(sprintf("Servers that should be disabled: %s", implode(', ', $result->disabledServers)));
            }
          }
      }
      if (!empty($result->addedServers) || !empty($result->disabledServers)) {
        // The system was not healthy when the server command was executed. The
        // system configuration may have been corrected as a result, but
        // indicate to the caller in the most obvious way available that the
        // system was in fact unhealthy.
        $exit_code = 1;
      }
    } elseif (strcmp($action, 'list') === 0) {
      // Show the servers currently in the database.
      $wip_servers = $this->getServers();
      $enabled_servers = array();
      $disabled_servers = array();
      $result = new \stdClass();
      foreach ($wip_servers as $server) {
        if ($server->getStatus() === ServerStatus::NOT_AVAILABLE) {
          $disabled_servers[] = $server->getHostname();
        } else {
          $enabled_servers[] = $server->getHostname();
        }
      }
      if (!empty($enabled_servers)) {
        $result->enabled = $enabled_servers;
      }
      if (!empty($disabled_servers)) {
        $result->disabled = $disabled_servers;
      }
      switch ($format) {
        case 'json':
          $output->writeln(json_encode($result));
          break;

        default:
          if (!empty($result->enabled)) {
            $output->writeln('Enabled servers:');
            foreach ($result->enabled as $hostname) {
              $output->writeln(sprintf("\t%s", $hostname));
            }
          }
          if (!empty($result->disabled)) {
            $output->writeln('Disabled servers:');
            foreach ($result->disabled as $hostname) {
              $output->writeln(sprintf("\t%s", $hostname));
            }
          }
      }
    }

    return $exit_code;
  }

  /**
   * Verifies the server table matches the Acquia Hosting configuration.
   *
   * @param bool $fix
   *   If TRUE, the server configuration will be modified to match the hosting
   *   configuration. Otherwise the differences will be calculated.
   * @param string[] $activate_servers
   *   Optional. The list of servers currently being activated. These will be
   *   verified in the server_store table regardless of whether they are
   *   currently marked as active in Acquia Hosting. This list is used when a
   *   webnode is in the process of being activated by Acquia Hosting.
   *
   * @return object
   *   An object containing the verification data.
   */
  private function verifyServerTable($fix, $activate_servers = array()) {
    $result = new \stdClass();

    // Get the servers from the database.
    $wip_servers = $this->getServers();

    // The servers in the database must be a subset of the active servers.
    $servers = $this->getHostingServers();
    $webnodes = $this->getHostingWebnodes($servers, $activate_servers);
    $add_servers = array_diff(array_keys($webnodes), array_keys($wip_servers));
    if ($fix) {
      $added_servers = $this->addServers($add_servers, $webnodes);
      if (!empty($added_servers)) {
        WipLog::getWipLog($this->dependencyManager)
          ->log(
            WipLogLevel::ALERT,
            sprintf(
              '***INTERNAL SYSTEM FAILURE*** System configuration change - added webnodes %s.',
              implode(', ', $added_servers)
            )
          );
      }
    } else {
      $added_servers = $add_servers;
    }
    if (count($add_servers) > 0) {
      $result->addedServers = $added_servers;
    }

    $remove_servers = array_diff(array_keys($wip_servers), array_keys($webnodes));

    // Strip out the servers that are already disabled.
    $servers_to_disable = array();
    foreach ($remove_servers as $server_name) {
      if (!empty($wip_servers[$server_name])) {
        $server = $wip_servers[$server_name];
        if ($server->getStatus() !== ServerStatus::NOT_AVAILABLE) {
          $servers_to_disable[] = $server_name;
        }
      }
    }
    if ($fix) {
      $disabled_servers = $this->disableServers($servers_to_disable, $wip_servers);
      if (!empty($disabled_servers)) {
        WipLog::getWipLog($this->dependencyManager)
          ->log(
            WipLogLevel::FATAL,
            sprintf(
              '***INTERNAL SYSTEM FAILURE*** System configuration change - disabled webnodes %s.',
              implode(', ', $disabled_servers)
            )
          );
      }
    } else {
      $disabled_servers = $servers_to_disable;
    }
    if (count($disabled_servers) > 0) {
      $result->disabledServers = $disabled_servers;
    }
    return $result;
  }

  /**
   * Adds the specified servers.
   *
   * @param string[] $servers
   *   The list of fully-qualified host names to add.
   * @param AcquiaCloudServerInfo[] $webnodes
   *   The Acquia Hosting server details.
   *
   * @return string[]
   *   The servers that were added.
   */
  private function addServers($servers, $webnodes) {
    $result = array();
    if (count($servers) > 0) {
      $server_store = ServerStore::getServerStore($this->dependencyManager);
      foreach ($servers as $server_name) {
        if (!empty($webnodes[$server_name])) {
          $server = $webnodes[$server_name];
          $php_max_processes = max($server->getServices()['web']['php_max_procs'], 1);
          $wip_server = new Server($server_name);
          $wip_server->setTotalThreads($php_max_processes);
          $server_store->save($wip_server);
          $result[] = $server_name;
        }
      }
    }
    return $result;
  }

  /**
   * Disables the specified servers.
   *
   * @param string[] $servers
   *   The list of fully-qualified host names to disable.
   * @param Server[] $wip_servers
   *   The wip server details.
   *
   * @return string[]
   *   The list of servers that were disabled.
   */
  private function disableServers($servers, $wip_servers) {
    $result = array();
    if (count($servers) > 0) {
      $server_store = ServerStore::getServerStore($this->dependencyManager);
      foreach ($servers as $server) {
        if (!empty($wip_servers[$server])) {
          $wip_server = $wip_servers[$server];
          if ($wip_server->getStatus() !== ServerStatus::NOT_AVAILABLE) {
            $wip_server->setStatus(ServerStatus::NOT_AVAILABLE);
            $server_store->remove($wip_server);
            $result[] = $server;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Gets the set of hosting servers.
   *
   * @return AcquiaCloudServerInfo[]
   *   The set of servers.
   *
   * @throws ServerErrorResponseException
   *   If the call to list servers fails.
   */
  private function getHostingServers() {
    $result = array();
    $environment = $this->getCloudEnvironment();
    $cloud = new AcquiaCloud($environment);
    $server_result = $cloud->listServers();
    if (!$server_result->isSuccess()) {
      throw new ServerErrorResponseException('Failed to list servers.');
    }
    $all_hosting_servers = $server_result->getData();

    foreach ($all_hosting_servers as $server) {
      $result[] = $server;
    }
    return $result;
  }

  /**
   * Gets the set of hosting webnodes that are enabled.
   *
   * @param AcquiaCloudServerInfo[] $servers
   *   The entire list of hosting servers.
   * @param string[] $activate_servers
   *   The list of servers currently being activated. These will be verified in
   *   the server_store table regardless of whether they are currently marked
   *   as active in Acquia Hosting.
   *
   * @return AcquiaCloudServerInfo[]
   *   An array of fully-qualified host names representing the active webnodes.
   */
  private function getHostingWebnodes($servers, $activate_servers) {
    /** @var AcquiaCloudServerInfo[] $servers */
    $result = array();
    foreach ($servers as $server) {
      $services = $server->getServices();

      // Only add the server if it is an active webnode.
      if (in_array('web', array_keys($services))
        && $services['web']['status'] === 'online'
        && ($services['web']['env_status'] === 'active' ||
          in_array($server->getFullyQualifiedDomainName(), $activate_servers)
        )
      ) {
        $result[$server->getFullyQualifiedDomainName()] = $server;
      }
    }
    return $result;
  }

  /**
   * Gets the set of enabled servers for Wip to execute tasks on.
   *
   * @return Server[]
   *   The fully-qualified host names.
   */
  private function getServers() {
    $result = array();
    $db_servers = ServerStore::getServerStore()->getAllServers();
    foreach ($db_servers as $server) {
      $result[$server->getHostname()] = $server;
    }
    return $result;
  }

  /**
   * Gets the Environment instance to use with the Acquia Cloud API.
   *
   * @return EnvironmentInterface
   *   The environment.
   */
  private function getCloudEnvironment() {
    $result = Environment::getRuntimeEnvironment();
    $cred_file = sprintf(
      '/mnt/files/%s.%s/nobackup/cloudapi.ini',
      $result->getSitegroup(),
      $result->getEnvironmentName()
    );
    $cloud_credentials = parse_ini_file($cred_file, TRUE);
    $credentials = new CloudCredentials(
      $cloud_credentials['endpoint'],
      $cloud_credentials[$result->getSitegroup()]['username'],
      $cloud_credentials[$result->getSitegroup()]['password'],
      $result->getFullyQualifiedSitegroup()
    );
    $result->setRealm($cloud_credentials['stage']);
    $result->setCloudCredentials($credentials);
    return $result;
  }

}
