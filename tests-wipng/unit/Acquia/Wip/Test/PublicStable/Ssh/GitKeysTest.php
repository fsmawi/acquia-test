<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

use Acquia\Wip\Ssh\GitKey;
use Acquia\Wip\Ssh\GitKeys;

/**
 * Tests the GitKeys class.
 */
class GitKeysTest extends \PHPUnit_Framework_TestCase {

  /**
   * Verifies the constructor works.
   */
  public function testConstructor() {
    $git_keys = new GitKeys();
    $this->assertNotEmpty($git_keys);

    $key_names = $git_keys->getAllKeyNames();
    $this->assertInternalType('array', $key_names);
    $this->assertEmpty($key_names);

    $key_values = $git_keys->getAllKeys();
    $this->assertInternalType('array', $key_values);
    $this->assertEmpty($key_values);
  }

  /**
   * Verifies the key add functionality.
   */
  public function testKey() {
    $name = 'name';
    $key = 'my ssh key';

    $git_key = new GitKey($name, NULL, NULL, $key);
    $git_keys = new GitKeys();
    $git_keys->addKey($git_key);

    $this->assertTrue($git_keys->hasKey($name));
    $this->assertEquals($git_key, $git_keys->getKey($name));
    $this->assertContains($name, $git_keys->getAllKeyNames());
    $this->assertContains($git_key, $git_keys->getAllKeys());
  }

  /**
   * Verifies keys can be removed.
   */
  public function testRemoveKey() {
    $name1 = 'name1';
    $key1 = 'my first ssh key';
    $name2 = 'name2';
    $key2 = 'my second ssh key';

    $git_key_1 = new GitKey($name1, NULL, NULL, $key1);
    $git_key_2 = new GitKey($name2, NULL, NULL, $key2);
    $git_keys = new GitKeys();
    $git_keys->addKey($git_key_1);
    $git_keys->addKey($git_key_2);

    $this->assertEquals(array($name1, $name2), $git_keys->getAllKeyNames());
    $this->assertEquals(array($git_key_1, $git_key_2), array_values($git_keys->getAllKeys()));
    $git_keys->removeKey($name1);
    $this->assertEquals(array($name2), $git_keys->getAllKeyNames());
    $this->assertEquals(array($git_key_2), array_values($git_keys->getAllKeys()));
  }

}
