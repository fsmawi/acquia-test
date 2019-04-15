<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\DependencyManager;
use Acquia\Wip\Exception\DependencyMissingException;
use Acquia\Wip\Test\PublicStable\Resource\TestObject2;
use Acquia\Wip\Test\PublicStable\Resource\TestObjectInterface;
use Acquia\Wip\Test\PublicStable\Resource\TestObjectNoInterface;
use Acquia\Wip\WipFactory;

/**
 * Missing summary.
 */
class DependencyManagerTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var DependencyManager
   */
  private $dependencyManager;

  /**
   * Missing summary.
   */
  public function setup() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->reset();
  }

  /**
   * Missing summary.
   */
  public function testDependenciesCrud() {
    $this->dependencyManager->addDependencies(array(
      'acquia.wip.phpunit.testobject' => 'Acquia\Wip\Test\PublicStable\Resource\TestObjectInterface',
    ));

    $instance = $this->dependencyManager->getDependency('acquia.wip.phpunit.testobject');

    $this->assertInstanceOf('Acquia\Wip\Test\PublicStable\Resource\TestObjectInterface', $instance);
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\DependencyMissingException
   */
  public function testGetNonexistent() {
    $this->dependencyManager->getDependency('this.does.not.exist');
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\DependencyTypeException
   */
  public function testBadTypeAdd() {
    $this->dependencyManager->addDependencies(array(
      'acquia.wip.phpunit.testobject' => 'NonExistentType',
    ));
  }

  /**
   * Missing summary.
   */
  public function testEmptyTypeAdd() {
    // This should not be type-checked at all.
    $this->dependencyManager->addDependencies(array(
      'acquia.wip.phpunit.testobject' => '',
    ));

    $instance = $this->dependencyManager->getDependency('acquia.wip.phpunit.testobject');
    // Due to /configuration/, this is true, not due to type checking.
    $this->assertInstanceOf('Acquia\Wip\Test\PublicStable\Resource\TestObjectInterface', $instance);
    // This should succeed, because we specified no type checking. See
    // testBadTypeSwap() to see an example type-checked fail.
    $this->dependencyManager->swapDependency('acquia.wip.phpunit.testobject', new \stdClass());
  }

  /**
   * Missing summary.
   */
  public function testSwap() {
    $this->dependencyManager->addDependencies(array(
      'acquia.wip.phpunit.testobject' => 'Acquia\Wip\Test\PublicStable\Resource\TestObjectInterface',
    ));
    $this->dependencyManager->swapDependency('acquia.wip.phpunit.testobject', new TestObject2());
  }

  /**
   * Missing summary.
   *
   * @expectedException \Acquia\Wip\Exception\DependencyTypeException
   */
  public function testBadTypeSwap() {
    $this->dependencyManager->addDependencies(array(
      'acquia.wip.phpunit.testobject' => 'Acquia\Wip\Test\PublicStable\Resource\TestObjectInterface',
    ));
    $this->dependencyManager->swapDependency('acquia.wip.phpunit.testobject', new TestObjectNoInterface());
  }

  /**
   * Missing summary.
   */
  public function testMissingSwap() {
    $exception = FALSE;
    try {
      // This swap is not allowed because no dependency spec was initially
      // added during this test - this is a missing dependency.
      $this->dependencyManager->swapDependency('acquia.wip.phpunit.testobject', new TestObjectNoInterface());
    } catch (DependencyMissingException $e) {
      $exception = TRUE;
    }

    $this->assertTrue($exception);
  }

  /**
   * Missing summary.
   */
  public function testSerialize() {
    $this->dependencyManager->addDependencies(array(
      'acquia.wip.phpunit.testobject' => 'Acquia\Wip\Test\PublicStable\Resource\TestObjectInterface',
    ));

    $instance = $this->dependencyManager->getDependency('acquia.wip.phpunit.testobject');

    $this->assertInstanceOf('Acquia\Wip\Test\PublicStable\Resource\TestObjectInterface', $instance);

    $serialized = serialize($this->dependencyManager);
    $restored = unserialize($serialized);

    $this->assertEquals($this->dependencyManager, $restored);
  }

  /**
   * Tests that the dependency manager does not get caught in an infinite loop.
   */
  public function testInterdependencyLoop() {
    $this->dependencyManager->addDependencies(array(
      'acquia.wip.phpunit.interdependenta' => 'Acquia\Wip\Test\PublicStable\Resource\InterdependentA',
      'acquia.wip.phpunit.interdependentb' => 'Acquia\Wip\Test\PublicStable\Resource\InterdependentB',
    ));
    $this->dependencyManager->getDependency('acquia.wip.phpunit.interdependenta');
    $this->dependencyManager->getDependency('acquia.wip.phpunit.interdependentb');
  }

}
