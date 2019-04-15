<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Ssh\GitKey;
use PHPUnit_Framework_TestCase;

/**
 * Tests the GitKey class.
 */
class GitKeyTest extends \PHPUnit_Framework_TestCase {

  /**
   * Verifies that the constructor works with no arguments.
   */
  public function testEmptyConstructor() {
    $git_key = new GitKey();
    $this->assertNotEmpty($git_key);
  }

  /**
   * Verifies the constructor sets all of the properties.
   */
  public function testConstructor() {
    $name = 'name';
    $private_key = 'test_key';
    $wrapper_name = 'wrapper';
    $key = 'ssh_key';
    $git_key = new GitKey($name, $private_key, $wrapper_name, $key);
    $this->assertEquals($name, $git_key->getName());
    $this->assertEquals($private_key, $git_key->getPrivateKeyFilename());
    $this->assertEquals($wrapper_name, $git_key->getWrapperFilename());
    $this->assertEquals($key, $git_key->getKey());
  }

  /**
   * Verifies the forget key functionality.
   */
  public function testForgetKey() {
    $key = 'ssh_key';
    $git_key = new GitKey();
    $git_key->setKey($key);
    $this->assertEquals($key, $git_key->getKey());
    $git_key->forgetKey();
    $this->assertEmpty($git_key->getKey());
  }

  /**
   * Verifies the key is encrypted at rest.
   */
  public function testKeyEncryption() {
    $key = 'this is my SSH key';
    $git_key = new GitKey();
    $git_key->setKey($key);

    $serialized_object = serialize($git_key);
    $this->assertNotContains($key, $serialized_object);

    /** @var GitKey $object */
    $object = unserialize($serialized_object);
    $this->assertEquals($key, $object->getKey());
  }

}
