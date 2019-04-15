<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\AcquiaCloud\CloudCredentials;

/**
 * Tests the CloudCredentials class.
 */
class CloudCredentialsTest extends \PHPUnit_Framework_TestCase {

  /**
   * Verifies the password field is not stored in clear text.
   */
  public function testPasswordIsSecure() {
    $unique_string = sha1(strval(mt_rand()));
    $credentials = new CloudCredentials('endpoint', 'user', $unique_string, 'sitegroup');
    $this->assertNotContains($unique_string, serialize($credentials));
  }

  /**
   * Verifies the getter methods work.
   */
  public function testGetters() {
    $endpoint = 'endpoint';
    $user = 'user';
    $password = 'password';
    $sitegroup = 'sitegroup';
    $credentials = new CloudCredentials($endpoint, $user, $password, $sitegroup);
    $this->assertEquals($endpoint, $credentials->getEndpoint());
    $this->assertEquals($user, $credentials->getUsername());
    $this->assertEquals($password, $credentials->getPassword());
    $this->assertEquals($sitegroup, $credentials->getSitegroup());
  }

}
