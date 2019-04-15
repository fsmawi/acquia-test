<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Environment;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;

/**
 * Missing summary.
 */
class EnvironmentTest extends \PHPUnit_Framework_TestCase {

  /**
   * The Wip ID associated with the environment.
   *
   * @var int
   */
  private $wipId;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->wipId = mt_rand(1, PHP_INT_MAX);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testCreation() {
    $env = new Environment();
    $this->assertInstanceOf('Acquia\Wip\EnvironmentInterface', $env);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testGetSiteGroup() {
    $group = 'test';
    $env = new Environment();
    $env->setSitegroup($group);
    $this->assertEquals($group, $env->getSitegroup());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSiteGroupNull() {
    $env = new Environment();
    $env->setSitegroup(NULL);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSiteGroupInt() {
    $env = new Environment();
    $env->setSitegroup(42);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSiteGroupEmptyString() {
    $env = new Environment();
    $env->setSitegroup('');
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testEnvironmentName() {
    $name = 'test';
    $env = new Environment();
    $env->setEnvironmentName($name);
    $this->assertEquals($name, $env->getEnvironmentName());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testEnvironmentNameNull() {
    $env = new Environment();
    $env->setEnvironmentName(NULL);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testEnvironmentNameInt() {
    $env = new Environment();
    $env->setEnvironmentName(42);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testEnvironmentNameEmptyString() {
    $env = new Environment();
    $env->setEnvironmentName('');
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testServers() {
    $servers = array('server1', 'server2', 'server3');
    $env = new Environment();
    $env->setServers($servers);
    $this->assertEquals($servers, $env->getServers());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testServersNull() {
    $env = new Environment();
    $env->setServers(NULL);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testServersEmptyArray() {
    $env = new Environment();
    $env->setServers(array());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testServersNonStringElement() {
    $servers = array('server1', 'server2', 15, 'server4');
    $env = new Environment();
    $env->setServers($servers);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testServersEmptyElement() {
    $servers = array('server1', 'server2', '', 'server4');
    $env = new Environment();
    $env->setServers($servers);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testCurrentServer() {
    $server_list = array('server1', 'server2', 'test', 'server4');
    $server = 'test';
    $env = new Environment();
    $env->setServers($server_list);
    $env->setCurrentServer($server);
    $this->assertEquals($server, $env->getCurrentServer());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testCurrentServerWithNoServersSet() {
    $server = 'test';
    $env = new Environment();
    $env->setCurrentServer($server);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \InvalidArgumentException
   */
  public function testCurrentServerNotInServersArray() {
    $server_list = array('server1', 'server2', 'server3', 'server4');
    $server = 'test';
    $env = new Environment();
    $env->setServers($server_list);
    $env->setCurrentServer($server);
    $this->assertEquals($server, $env->getCurrentServer());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testNextServer() {
    $server_list = array('server1', 'server2', 'server3', 'server4');
    $env = new Environment();
    $env->setServers($server_list);
    $original_server = $env->getCurrentServer();
    $server = $env->selectNextServer();
    $this->assertTrue(in_array($server, $server_list));
    $this->assertNotEquals($original_server, $server);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testNextServerNoServerList() {
    $env = new Environment();
    $env->selectNextServer();
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testNextServerNoDuplicates() {
    $server_list = array(
      'server1',
      'server2',
      'server3',
      'server4',
      'server5',
      'server6',
      'server7',
      'server8',
      'server9',
      'server10',
    );
    $env = new Environment();
    $env->setServers($server_list);
    $selected_servers = array();
    for ($i = 0; $i < count($server_list); $i++) {
      $selected_servers[] = $env->selectNextServer();
    }
    $diff = array_diff($server_list, $selected_servers);
    $this->assertEquals(array(), $diff);

    // Make certain these are exactly the same except for order.
    $diff2 = array_diff($selected_servers, $server_list);
    $this->assertEquals(array(), $diff2);

    $this->assertNotEquals($server_list, $selected_servers);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testNextServerWrapNoDuplicates() {
    $server_list = array(
      'server1',
      'server2',
      'server3',
      'server4',
      'server5',
      'server6',
      'server7',
      'server8',
      'server9',
      'server10',
    );
    $env = new Environment();
    $env->setServers($server_list);
    $selected_servers_1 = array();
    for ($i = 0; $i < count($server_list); $i++) {
      $selected_servers_1[] = $env->selectNextServer();
    }
    $diff = array_diff($server_list, $selected_servers_1);
    $this->assertEquals(array(), $diff);

    // Do it again and see if the lists are the same.
    $selected_servers_2 = array();
    for ($i = 0; $i < count($server_list); $i++) {
      $selected_servers_2[] = $env->selectNextServer();
    }
    $this->assertEquals($selected_servers_1, $selected_servers_2);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testRuntimeEnvironment() {
    $sitegroup = 'sitegroup';
    $environment_name = 'environment';
    Environment::setRuntimeSitegroup($sitegroup);
    Environment::setRuntimeEnvironmentName($environment_name);
    $runtime_environment = Environment::getRuntimeEnvironment();

    $this->assertEquals($sitegroup, $runtime_environment->getSitegroup());
    $this->assertEquals($environment_name, $runtime_environment->getEnvironmentName());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testGetRuntimeSitegroupNotSet() {
    $success = FALSE;
    environment::setRuntimeSitegroup(NULL);
    if (!empty($_ENV['AH_SITE_GROUP'])) {
      $sitegroup = $_ENV['AH_SITE_GROUP'];
      putenv('AH_SITE_GROUP=');
      $_ENV['AH_SITE_GROUP'] = NULL;
    }
    try {
      $sitegroup_value = getenv('AH_SITE_GROUP');
      $this->assertEmpty($sitegroup_value, 'Failed to unset the AH_SITE_GROUP environment variable for this test.');
      Environment::getRuntimeSitegroup();
    } catch (\RuntimeException $e) {
      // We expect an exception here, but we have to do a cleanup.
      $success = TRUE;
    }
    finally {
      if (!empty($sitegroup)) {
        $_ENV['AH_SITE_GROUP'] = $sitegroup;
        putenv('AH_SITE_GROUP=' . $sitegroup);
      }
    }
    $this->assertTrue($success, 'Failed to throw \RuntimeException when the sitegroup is not set.');
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testGetRuntimeEnvironmentNameNotSet() {
    $success = FALSE;
    environment::setRuntimeEnvironmentName(NULL);
    if (!empty($_ENV['AH_SITE_ENVIRONMENT'])) {
      $env = $_ENV['AH_SITE_ENVIRONMENT'];
      putenv('AH_SITE_ENVIRONMENT=');
      $_ENV['AH_SITE_ENVIRONMENT'] = NULL;
    }
    try {
      $env_value = getenv('AH_SITE_ENVIRONMENT');
      $this->assertEmpty($env_value, 'Failed to unset the AH_SITE_ENVIRONMENT environment variable for this test.');
      Environment::getRuntimeEnvironmentName();
    } catch (\RuntimeException $e) {
      $success = TRUE;
    }
    finally {
      if (!empty($env)) {
        $_ENV['AH_SITE_ENVIRONMENT'] = $env;
        putenv('AH_SITE_ENVIRONMENT=' . $env);
      }
    }
    $this->assertTrue($success, 'Failed to throw \RuntimeException when the environment is not set.');
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testGetDocrootDir() {
    $environment = new Environment();
    $environment->setSitegroup('site');
    $environment->setEnvironmentName('env');
    $docroot_dir = $environment->getDocrootDir();
    $this->assertEquals('/mnt/www/html/site.env/docroot', $docroot_dir);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testGetDocrootDirNoSitegroup() {
    $environment = new Environment();
    $environment->setEnvironmentName('env');
    $environment->getDocrootDir();
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testGetDocrootDirNoEnvironmentName() {
    $environment = new Environment();
    $environment->setSitegroup('site');
    $environment->getDocrootDir();
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testSetDocrootDir() {
    $environment = new Environment();
    $environment->setDocrootDir('/tmp');
    $this->assertEquals('/tmp', $environment->getDocrootDir());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testGetWorkingDir() {
    $environment = new Environment();
    $environment->setSitegroup('site');
    $environment->setEnvironmentName('env');
    $working_dir = $environment->getWorkingDir();
    $this->assertEquals('/mnt/tmp/site.env', $working_dir);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testGetWorkingDirNoSitegroup() {
    $environment = new Environment();
    $environment->setEnvironmentName('env');
    $environment->getWorkingDir();
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   *
   * @expectedException \RuntimeException
   */
  public function testGetWorkingDirNoEnvironmentName() {
    $environment = new Environment();
    $environment->setSitegroup('site');
    $environment->getWorkingDir();
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testSetWorkingDir() {
    $environment = new Environment();
    $environment->setWorkingDir('/tmp');
    $this->assertEquals('/tmp', $environment->getWorkingDir());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testMakeEnvironment() {
    $creds = AcquiaCloudTestSetup::getCreds();
    $env = Environment::makeEnvironment(
      AcquiaCloudTestSetup::createWipLog(),
      $this->wipId,
      $creds->getSitegroup(),
      $creds->getEndpoint(),
      $creds->getUsername(),
      $creds->getPassword()
    );
    $this->assertInstanceOf('Acquia\Wip\EnvironmentInterface', $env);
    $this->assertEquals($creds->getSitegroup(), $env->getFullyQualifiedSitegroup());
    $this->assertTrue(count($env->getServers()) > 0);
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testMakeEnvironmentBackfillEnv() {
    // Even when the ACQUIA_CLOUD_ENVIRONMENT environment variable is not set,
    // we should get the environment name backfilled from cloud.
    $creds = AcquiaCloudTestSetup::getCreds();
    $previous_env = getenv('ACQUIA_CLOUD_ENVIRONMENT');
    putenv('ACQUIA_CLOUD_ENVIRONMENT=');
    $env = Environment::makeEnvironment(
      AcquiaCloudTestSetup::createWipLog(),
      $this->wipId,
      $creds->getSitegroup(),
      $creds->getEndpoint(),
      $creds->getUsername(),
      $creds->getPassword()
    );
    $this->assertInstanceOf('Acquia\Wip\EnvironmentInterface', $env);
    $this->assertEquals($creds->getSitegroup(), $env->getFullyQualifiedSitegroup());
    $this->assertTrue(count($env->getServers()) > 0);
    putenv("ACQUIA_CLOUD_ENVIRONMENT=$previous_env");
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testMakeEnvironmentMissingVars() {
    $variables = array(
      'ACQUIA_CLOUD_SITEGROUP',
      'ACQUIA_CLOUD_ENDPOINT',
      'ACQUIA_CLOUD_USER',
      'ACQUIA_CLOUD_PASSWORD',
    );
    foreach ($variables as $name) {
      // Capture any existing value to restore later, then unset the env var.
      $previous = getenv($name);
      putenv("$name=");

      $exception = FALSE;
      try {
        $env = Environment::makeEnvironment(AcquiaCloudTestSetup::createWipLog(), $this->wipId);
      } catch (\InvalidArgumentException $e) {
        $exception = TRUE;
      }
      $this->assertTrue(empty($env));
      $this->assertTrue($exception);

      // Reset any changed environment vars back to the previous value.
      putenv("$name=$previous");
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testMakeEnvironmentRealmOverride() {
    $test_realm = getenv('ACQUIA_CLOUD_REALM');
    putenv('ACQUIA_CLOUD_REALM=');
    $env = Environment::makeEnvironment(
      AcquiaCloudTestSetup::createWipLog(),
      $this->wipId,
      NULL,
      NULL,
      NULL,
      NULL,
      NULL,
      TRUE,
      $test_realm
    );
    $this->assertInstanceOf('Acquia\Wip\EnvironmentInterface', $env);
    $this->assertEquals($test_realm, $env->getRealm());
    putenv("ACQUIA_CLOUD_REALM=$test_realm");
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testMakeEnvironmentNoCloudInfo() {
    $env = Environment::makeEnvironment(AcquiaCloudTestSetup::createWipLog(), $this->wipId);
    $this->assertInstanceOf('Acquia\Wip\EnvironmentInterface', $env);
    $this->assertNotEmpty($env->getSitegroup());
    $this->assertNotEmpty($env->getEnvironmentName());
    $this->assertTrue(count($env->getServers()) > 0);
    $this->assertEquals(getenv('ACQUIA_CLOUD_REALM'), $env->getRealm());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testConfiguredRuntimeSitegroup() {
    Environment::setRuntimeSitegroup('');
    $sitegroup_name = rand(1, PHP_INT_MAX);
    $previous = getenv('AH_SITE_GROUP');
    putenv("AH_SITE_GROUP=$sitegroup_name");
    $this->assertEquals($sitegroup_name, Environment::getRuntimeSitegroup());
    putenv("AH_SITE_GROUP=$previous");

    $sitegroup_name = rand(1, PHP_INT_MAX);
    Environment::setRuntimeSitegroup($sitegroup_name);
    $this->assertEquals($sitegroup_name, Environment::getRuntimeSitegroup());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   * @group BuildSteps
   * @group Ssh
   * @group Wip
   * @group WipTask
   */
  public function testConfiguredRuntimeEnvironment() {
    Environment::setRuntimeEnvironmentName('');
    $environment_name = rand(1, PHP_INT_MAX);
    $previous = getenv('AH_SITE_ENVIRONMENT');
    putenv("AH_SITE_ENVIRONMENT=$environment_name");
    $this->assertEquals($environment_name, Environment::getRuntimeEnvironmentName());
    putenv("AH_SITE_ENVIRONMENT=$previous");

    $environment_name = rand(1, PHP_INT_MAX);
    Environment::setRuntimeEnvironmentName($environment_name);
    $this->assertEquals($environment_name, Environment::getRuntimeEnvironmentName());
  }

  /**
   * Verifies the password is stored in the Environment instance securely.
   */
  public function testPasswordIsSecure() {
    $unique_string = sha1(strval(mt_rand()));
    $environment = new Environment();
    $environment->setPassword($unique_string);
    $this->assertNotContains($unique_string, serialize($environment));
  }

}
