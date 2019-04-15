<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\SignalCallbackStore;
use Acquia\WipIntegrations\DoctrineORM\SignalStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\WipService\Utility\SignalCallbackHttpTransport;

/**
 * Missing summary.
 */
class SignalResourceTest extends AbstractFunctionalTest {

  /**
   * Missing summary.
   *
   * @var SignalCallbackStore
   */
  private $storage;

  /**
   * Missing summary.
   *
   * @var SignalStoreInterface
   */
  private $signalStorage;

  /**
   * Missing summary.
   *
   * @var SignalCallbackHttpTransport
   */
  private $handler;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->storage = new SignalCallbackStore();
    $this->signalStorage = new SignalStore();
    $this->handler = new SignalCallbackHttpTransport();
  }

  /**
   * Tests the POST method.
   */
  public function testPostAction() {
    $client = $this->createClient('ROLE_ADMIN');

    $urls = array();
    for ($i = 0; $i < 5; ++$i) {
      $wip_id = 500 * $i + rand(0, 499);
      $url = $this->handler->getCallbackUrl($wip_id);
      $urls[$url] = $wip_id;
    }

    foreach ($urls as $url => $wip_id) {
      // Check no existing signal before the callback.
      $this->assertEmpty($this->signalStorage->loadAll($wip_id));

      // Request body is a valid but empty JSON doc.
      $client->request('POST', $url, array(), array(), array(), '{}');

      $response = $client->getResponse();

      $this->assertTrue($response->isOk());
      $content = json_decode($response->getContent());
      $this->assertEquals($wip_id, $content->id);

      // Check signal exists after calling the callback.
      $this->assertNotEmpty($this->signalStorage->loadAll($wip_id));
      $path_fragments = parse_url($url);
      list(,,, $uuid) = explode('/', $path_fragments['path']);
      $this->assertNotEmpty($uuid, 'There was a problem parsing the uuid from the signal.');

      $entry = $this->storage->load($uuid);
      $this->storage->delete($entry);

      // Request body is a valid but empty JSON doc.
      $client->request('POST', $url, array(), array(), array(), '{}');

      // No longer reachable once deleted.
      $response = $client->getResponse();
      $this->assertFalse($response->isOk());
    }
  }

  /**
   * Tests the POST method.
   */
  public function testBadId() {
    $client = $this->createClient('ROLE_ADMIN');
    // Request body is a valid but empty JSON doc; the ID 123 can never be
    // valid, as it's too short.
    $client->request('POST', '/signal/123', array(), array(), array(), '{}');

    $this->assertFalse($client->getResponse()->isOk());

    $this->assertEquals(500, $client->getResponse()->getStatusCode());
  }

  /**
   * Missing summary.
   */
  public function testDomainSpecificSignal() {
    $client = $this->createClient('ROLE_ADMIN');

    $signal_types = array(
      array(
        'data' => array(
          'classId' => '$acquia.wip.signal.ssh.complete',
          'server' => 'localhost',
          'startTime' => time() - 10,
          'pid' => rand(200, 20000),
        ),
        'type' => '\Acquia\Wip\Signal\SshCompleteSignal',
      ),
      array(
        'data' => array(
          'classId' => '$acquia.wip.signal.wip.complete',
          'completedWipId' => 0,
          'startTime' => time() - 10,
        ),
        'name' => '$acquia.wip.signal.wip.complete',
        'type' => '\Acquia\Wip\Signal\WipCompleteSignal',
      ),
    );

    foreach ($signal_types as $i => $signal_type) {
      $wip_id = 1000000 * $i + rand(0, 999999);

      // Required by the WIP signal type.
      if (isset($signal_type['data']['completedWipId'])) {
        $signal_type['data']['completedWipId'] = $wip_id;
      }

      $url = $this->handler->getCallbackUrl($wip_id);

      // Pass classId and other required data in the JSON to get a
      // domain-specific signal.
      $client->request('POST', $url, array(), array(), array(), json_encode((object) $signal_type['data']));

      $response = $client->getResponse();

      $this->assertTrue($response->isOk());
      $content = json_decode($response->getContent());
      $this->assertEquals($wip_id, $content->id);

      // Check signal exists after calling the callback.
      $this->assertNotEmpty($loaded_signals = $this->signalStorage->loadAll($wip_id));
      $this->assertCount(1, $loaded_signals);
      $this->assertInstanceOf($signal_type['type'], reset($loaded_signals));
    }
  }

  /**
   * Tests that the user role cannot send a signal.
   */
  public function testPostSignalByUserRole() {
    $client = $this->createClient('ROLE_USER');
    $url = $this->handler->getCallbackUrl(1);
    $client->request('POST', $url, array(), array(), array(), '{}');
    $response = $client->getResponse();
    $this->assertTrue($response->isForbidden());
  }

}
