<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\Iterators\BasicIterator\StateTableRecording;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\Storage\WipStoreInterface;
use Acquia\Wip\TaskExitStatus;
use Acquia\Wip\TaskInterface;
use Acquia\Wip\Test\PrivateStable\Storage\WipPoolStoreTest;
use Acquia\Wip\WipFactory;

/**
 * Tests the SimulationResource class.
 */
class SimulationResourceTest extends AbstractFunctionalTest {

  /**
   * The URI pattern for getting the transcript.
   */
  const TRANSCRIPT_URI = '/tasks/%d/transcript';

  /**
   * The URI pattern for getting the simulation script.
   */
  const SIMULATION_SCRIPT_URI = '/tasks/%d/simulation-script';

  /**
   * The wip pool object.
   *
   * @var WipPoolStoreInterface
   */
  private $wipPool;

  /**
   * The object storage.
   *
   * @var WipStoreInterface
   */
  private $objectStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    WipFactory::setConfigPath('config/config.factory.test.cfg');
    $this->wipPool = WipFactory::getObject('acquia.wip.storage.wippool');
    $this->objectStorage = WipFactory::getObject('acquia.wip.storage.wip');
  }

  /**
   * Gets a client with the specified security role.
   *
   * @param string $role
   *   The role.
   *
   * @return \Symfony\Component\HttpKernel\Client
   *   The client with the specified role.
   */
  private function getClient($role = 'ROLE_ADMIN') {
    return $this->createClient($role);
  }

  /**
   * Provides uri patterns for the transcript and simulation script endpoints.
   */
  public function uriPatternProvider() {
    return array(
      array(self::SIMULATION_SCRIPT_URI),
      array(self::TRANSCRIPT_URI),
    );
  }

  /**
   * Creates and saves a new Task object with the given exit status and role.
   *
   * @param int $status
   *   The exit status.
   * @param string $role
   *   optional. The security role, either ROLE_USER or ROLE_ADMIN. If none is
   *   provided, ROLE_ADMIN is used.
   *
   * @return TaskInterface
   *   The task.
   */
  private function createAndSaveTask($status, $role = 'ROLE_ADMIN') {
    $uuid = $this->app['security.client_users'][$role]['username'];
    $task = WipPoolStoreTest::generateTask($uuid);
    $task->setExitStatus($status);
    $this->wipPool->save($task);
    $wip_id = $task->getId();
    $this->objectStorage->save($wip_id, $task->getWipIterator());

    return $task;
  }

  /**
   * Tests that the resource responds appropriately.
   */
  public function testGetSimulationScript() {
    $task = $this->createAndSaveTask(TaskExitStatus::COMPLETED);

    // Add entries to the recording.
    $task->getWipIterator()->addStateEntry('start');
    $task->getWipIterator()->addTransitionEntry('transition', 'value');
    $task->getWipIterator()->addStateEntry('finish');
    $this->objectStorage->save($task->getId(), $task->getWipIterator());

    $client = $this->getClient();
    $client->request('GET', sprintf(self::SIMULATION_SCRIPT_URI, $task->getId()));
    $response = $client->getResponse();

    // Check that we got the expected 200 status code.
    $this->assertEquals(200, $response->getStatusCode());

    // Check that 'simulation_scripts' exists in the response body.
    $content = json_decode($response->getContent(), TRUE);
    $this->assertNotEmpty($content);

    $simulation_scripts = $content['simulation_scripts'];
    $this->assertCount(1, $simulation_scripts);

    // Check that the simulation scripts returned are correct.
    $expected = <<<EOT
start {
  'value'
}

EOT;
    $this->assertEquals($expected, $simulation_scripts["Acquia\\Wip\\Implementation\\BasicWip"]);
  }

  /**
   * Tests that the resource responds appropriately.
   */
  public function testGetTranscript() {
    $task = $this->createAndSaveTask(TaskExitStatus::COMPLETED);

    // Add entries to the recording.
    $task->getWipIterator()->addStateEntry('start');
    $task->getWipIterator()->addTransitionEntry('transition', 'value');
    $task->getWipIterator()->addStateEntry('finish');
    $this->objectStorage->save($task->getId(), $task->getWipIterator());

    $client = $this->getClient();
    $client->request('GET', sprintf(self::TRANSCRIPT_URI, $task->getId()));
    $response = $client->getResponse();

    // Check that we got the expected 200 status code.
    $this->assertEquals(200, $response->getStatusCode());

    // Check that 'transcripts' exists in the response body.
    $content = json_decode($response->getContent(), TRUE);
    $this->assertNotEmpty($content);

    $transcripts = $content['transcripts'];
    $this->assertCount(1, $transcripts);

    // Check that the transcripts returned are correct.
    $expected = "start => 'value' => finish";
    $this->assertEquals($expected, $transcripts["Acquia\\Wip\\Implementation\\BasicWip"]);
  }

  /**
   * Tests that multiple Recording entries are returned correctly.
   */
  public function testGetSimulationScriptForMultipleRecordings() {
    $task = $this->createAndSaveTask(TaskExitStatus::COMPLETED);

    // Add entries to the first recording.
    $task->getWipIterator()->addStateEntry('start');
    $task->getWipIterator()->addTransitionEntry('transition', 'value');
    $task->getWipIterator()->addStateEntry('finish');

    // Create and add a second recording.
    $second_recording = new StateTableRecording();
    $second_recording->addState('start');
    $second_recording->addTransition('emptyTransition', '');
    $second_recording->addState('finish');
    $task->getWipIterator()->addRecording('second_recording', $second_recording);

    $this->objectStorage->save($task->getId(), $task->getWipIterator());

    $client = $this->getClient();
    $client->request('GET', sprintf(self::SIMULATION_SCRIPT_URI, $task->getId()));
    $response = $client->getResponse();

    // Check that we got the expected 200 status code.
    $this->assertEquals(200, $response->getStatusCode());

    // Check that 'simulation_scripts' exists in the response body.
    $content = json_decode($response->getContent(), TRUE);
    $this->assertNotEmpty($content);

    // Check that there are two simulation script entries: one from the
    // generated WIP object and one that was added earlier for testing.
    $simulation_scripts = $content['simulation_scripts'];
    $this->assertCount(2, $simulation_scripts);

    // Check that the simulation scripts returned are correct. The first entry
    // should be an empty string for the WIP object that never ran, and the
    // second entry should contain the StateTableRecording object added
    // earlier for testing.
    $expected_first_recording = <<<EOT
start {
  'value'
}

EOT;

    $expected_second_recording = <<<EOT
start {
  ''
}

EOT;

    $this->assertEquals($expected_first_recording, $simulation_scripts["Acquia\\Wip\\Implementation\\BasicWip"]);
    $this->assertEquals($expected_second_recording, $simulation_scripts['second_recording']);
  }

  /**
   * Tests that multiple Recording entries are returned correctly.
   */
  public function testGetTranscriptForMultipleRecordings() {
    $task = $this->createAndSaveTask(TaskExitStatus::COMPLETED);

    // Add entries to the first recording.
    $task->getWipIterator()->addStateEntry('start');
    $task->getWipIterator()->addTransitionEntry('transition', 'value');
    $task->getWipIterator()->addStateEntry('finish');

    // Create and add a second recording.
    $second_recording = new StateTableRecording();
    $second_recording->addState('start');
    $second_recording->addTransition('emptyTransition', '');
    $second_recording->addState('finish');
    $task->getWipIterator()->addRecording('second_recording', $second_recording);

    $this->objectStorage->save($task->getId(), $task->getWipIterator());

    $client = $this->getClient();
    $client->request('GET', sprintf(self::TRANSCRIPT_URI, $task->getId()));
    $response = $client->getResponse();

    // Check that we got the expected 200 status code.
    $this->assertEquals(200, $response->getStatusCode());

    // Check that 'transcripts' exists in the response body.
    $content = json_decode($response->getContent(), TRUE);
    $this->assertNotEmpty($content);

    // Check that there are two transcript entries: one from the generated
    // WIP object and one that was added earlier for testing.
    $transcripts = $content['transcripts'];
    $this->assertCount(2, $transcripts);

    // Check that the transcripts returned are correct. The first entry
    // should be an empty string for the WIP object that never ran, and the
    // second entry should contain the StateTableRecording object added
    // earlier for testing.
    $expected_first_recording = "start => 'value' => finish";
    $expected_second_recording = "start => '' => finish";
    $this->assertEquals($expected_first_recording, $transcripts["Acquia\\Wip\\Implementation\\BasicWip"]);
    $this->assertEquals($expected_second_recording, $transcripts['second_recording']);
  }

  /**
   * Tests that an error is returned for a WIP object in the NOT_FINISHED state.
   *
   * @param string $uri_pattern
   *   The URI provided by the uriPatternProvider.
   *
   * @dataProvider uriPatternProvider
   */
  public function testFailIfTaskIsNotCompleted($uri_pattern) {
    $incomplete_task = $this->createAndSaveTask(TaskExitStatus::NOT_FINISHED);

    $client = $this->getClient();
    $client->request('GET', sprintf($uri_pattern, $incomplete_task->getId()));
    $response = $client->getResponse();

    // Check that we got the expected 400 status code.
    $this->assertEquals(400, $response->getStatusCode());

    // Check that we got the correct error message.
    $content = json_decode($response->getContent());
    $this->assertEquals(sprintf('WIP object ID %d has not completed.', $incomplete_task->getId()), $content->message);
  }

  /**
   * Tests that an error is returned for a WIP ID that does not exist.
   *
   * @param string $uri_pattern
   *   The URI provided by the uriPatternProvider.
   *
   * @dataProvider uriPatternProvider
   */
  public function testFailIfIdDoesNotExist($uri_pattern) {
    $nonexistent_id = 9000;

    $client = $this->getClient();
    $client->request('GET', sprintf($uri_pattern, $nonexistent_id));
    $response = $client->getResponse();

    // Check that we got the expected 404 status code.
    $this->assertEquals(404, $response->getStatusCode());

    // Check that we got the correct error message.
    $content = json_decode($response->getContent());
    $this->assertEquals('Resource not found.', $content->message);
  }

  /**
   * Tests that an error is returned for a WIP ID that has no recordings.
   *
   * @param string $uri_pattern
   *   The URI provided by the uriPatternProvider.
   *
   * @dataProvider uriPatternProvider
   */
  public function testFailIfNoRecordingsExist($uri_pattern) {
    $task = $this->createAndSaveTask(TaskExitStatus::COMPLETED);

    // Do not add any recordings before calling the REST endpoint.
    $client = $this->getClient();
    $client->request('GET', sprintf($uri_pattern, $task->getId()));
    $response = $client->getResponse();

    // Check that we got the expected 404 status code.
    $this->assertEquals(404, $response->getStatusCode());

    // Check that we got the correct error message.
    $content = json_decode($response->getContent());
    $this->assertEquals(sprintf('No recordings found for WIP object ID %d.', $task->getId()), $content->message);
  }

  /**
   * Tests that a user role is not authorized to get any simulation data.
   *
   * @param string $uri_pattern
   *   The URI provided by the uriPatternProvider.
   *
   * @dataProvider uriPatternProvider
   */
  public function testUserCannotGetSimulationData($uri_pattern) {
    $client = $this->getClient('ROLE_USER');
    // The ID value doesn't matter because the request should fail before it is
    // needed.
    $client->request('GET', sprintf($uri_pattern, 1));
    $response = $client->getResponse();

    // Check that we got the expected 403 status code.
    $this->assertEquals(403, $response->getStatusCode());
  }

}
