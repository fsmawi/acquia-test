<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\DependencyManager;
use Acquia\Wip\Metrics\MetricsRelayInterface;

/**
 * Missing summary.
 */
class MetricsRelayTest extends \PHPUnit_Framework_TestCase {

  /**
   * The dependency manager.
   *
   * @var DependencyManager
   */
  protected $dependencyManager;

  /**
   * Missing summary.
   */
  public function setUp() {
    $this->dependencyManager = new DependencyManager();
    $this->dependencyManager->addDependencies(
      array(
        'acquia.wip.metrics.relay' => 'Acquia\Wip\Implementation\NullMetricsRelay',
      )
    );
    parent::setUp();
  }

  /**
   * Retrieves the metrics relay object.
   *
   * @return MetricsRelayInterface
   *   The metrics relay.
   */
  protected function getMetricsRelay() {
    return $this->dependencyManager->getDependency('acquia.wip.metrics.relay');
  }

  /**
   * Ensures that we are aware of interface changes.
   */
  public function testMetricsRelayInterface() {
    $relay = $this->getMetricsRelay();
    $class = new \ReflectionClass($relay);
    $this->assertArrayHasKey('Acquia\\Wip\\Metrics\\MetricsRelayInterface', $class->getInterfaces());
    $methods = array();
    foreach ($class->getMethods() as $method_reflection) {
      $methods[] = $method_reflection->name;
    }

    $this->assertContains('increment', $methods);
    $this->assertContains('decrement', $methods);
    $this->assertContains('count', $methods);
    $this->assertContains('timing', $methods);
    $this->assertContains('startTiming', $methods);
    $this->assertContains('endTiming', $methods);
    $this->assertContains('startMemoryProfile', $methods);
    $this->assertContains('endMemoryProfile', $methods);
    $this->assertContains('gauge', $methods);
    $this->assertContains('set', $methods);

    // There are currently no return values or behavior to test, but we want
    // to ensure coverage. If there are return values or behaviors implemented
    // in the future, assertions can be added below.
    $ns = 'my.test';
    $relay->increment($ns);
    $relay->decrement($ns);
    $relay->count($ns, 1);
    $relay->timing($ns, 1);
    $relay->startTiming($ns);
    $relay->endTiming($ns);
    $relay->startMemoryProfile($ns);
    $relay->endMemoryProfile($ns);
    $relay->gauge($ns, 1);
    $relay->set($ns, 1);
  }

}
