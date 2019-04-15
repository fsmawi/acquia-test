<?php

namespace Acquia\WipService\Test;

use Acquia\WipIntegrations\DoctrineORM\TaskDefinitionStore;
use Acquia\WipService\Test\AbstractFunctionalTest;

/**
 * Missing summary.
 */
class TaskDefinitionStoreTest extends AbstractFunctionalTest {

  /**
   * Missing summary.
   *
   * @var TaskDefinitionStore
   */
  private $taskDefinitionStore;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // For some reason the singleton produced by the WipFactory makes tests go
    // peculiar - after a few tests, it complains about the entity manager being
    // "closed". Leave this as a concrete object for now.
    $this->taskDefinitionStore = new TaskDefinitionStore();
  }

  /**
   * Missing summary.
   */
  public function testCrud() {
    // Test non-existent keys return NULL.
    $test_nonexistent_name = 'NOENTRY' . rand();
    $test_nonexistent_region = 'NOENTRY' . rand();
    $value = $this->taskDefinitionStore->get($test_nonexistent_name, $test_nonexistent_region);
    $this->assertNull($value);

    // Test saving an object and loading it again.
    $test_name = 'NAME' . rand();
    $test_region = 'REGIOND' . rand();
    $test_definition = array(
      'test1' => rand(),
      'test2' => array(
        'test3' => rand(),
      ),
    );
    $revision = 1;
    $this->taskDefinitionStore->save($test_name, $test_region, $test_definition, $revision);
    $value = $this->taskDefinitionStore->get($test_name, $test_region, $revision);
    $test_definition['revision'] = $revision;
    $this->assertEquals($test_definition, $value);
    // It should also work without specifying the revision, as it's the latest
    // revision we're after.
    $value = $this->taskDefinitionStore->get($test_name, $test_region);
    $this->assertEquals($test_definition, $value);

    // Test update existing records.
    $test_definition2 = array(
      'test1' => rand(),
      'test2' => array(
        'test3' => rand(),
      ),
    );
    $this->taskDefinitionStore->save($test_name, $test_region, $test_definition2, $revision);
    $value = $this->taskDefinitionStore->get($test_name, $test_region);
    $test_definition2['revision'] = $revision;
    $this->assertEquals($test_definition2, $value);

    // Test nonexistent keys still return nothing.
    $value = $this->taskDefinitionStore->get($test_nonexistent_name, $test_nonexistent_region);
    $this->assertNull($value);

    // Test deleting an object, and verify it can no longer be loaded.
    $value = $this->taskDefinitionStore->get($test_name, $test_region);
    $this->assertEquals($test_definition2, $value);

    $this->taskDefinitionStore->delete($test_name, $test_region, $revision);
    $value = $this->taskDefinitionStore->get($test_name, $test_region, $revision);
    $this->assertNull($value);
  }

}
