<?php

namespace Acquia\Wip\Test\PublicStable\Drupal;

use Acquia\Wip\Drupal\DrupalSite;
use Acquia\Wip\Drupal\DrushCommands;
use Acquia\Wip\Test\PublicStable\Ssh\SshTestSetup;

/**
 * Missing summary.
 */
class DrushCommandsTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testCacheClear() {
    $domains = array('domain1');
    $drupal_site = new DrupalSite($domains);
    $drupal_site = SshTestSetup::setUpLocalSsh(FALSE, $drupal_site);
    $drush_commands = new DrushCommands($drupal_site, SshTestSetup::createWipLog(), 15);
    $command = $drush_commands->getCacheClear();
    $this->assertEquals('cache-clear all', $command->getCommand());
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testCacheClearMenu() {
    $domains = array('domain1');
    $drupal_site = new DrupalSite($domains);
    $drupal_site = SshTestSetup::setUpLocalSsh(FALSE, $drupal_site);
    $drush_commands = new DrushCommands($drupal_site, SshTestSetup::createWipLog(), 15);
    $command = $drush_commands->getCacheClear('menu');
    $this->assertEquals('cache-clear menu', $command->getCommand());
  }

}
