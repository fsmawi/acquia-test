<?php

namespace Acquia\WipService\Test;

use Acquia\WipIntegrations\DoctrineORM\SignalCallbackStore;
use Acquia\WipService\App;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\WipService\Utility\SignalCallbackHttpTransport;

/**
 * Missing summary.
 */
class SignalCallbackHandlerTest extends AbstractFunctionalTest {

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

    $this->handler = new SignalCallbackHttpTransport();
  }

  /**
   * Missing summary.
   */
  public function testSignalCallback() {
    $urls = array();
    for ($i = 0; $i <= rand(100, 500); ++$i) {
      $wip_id = rand(1, 1000000);

      $url = $this->handler->getCallbackUrl($wip_id);
      $this->assertRegExp('@/v1/signal/[a-zA-Z0-9-_]{32,}@', $url);

      $urls[$url] = $wip_id;
    }

    $storage = new SignalCallbackStore();
    $loaded = $storage->loadAll();

    // Check the number we added corresponds to the total entries in the table.
    $this->assertCount($i, $loaded);

    foreach ($urls as $url => $wip_id) {
      $path_fragments = parse_url($url);
      list(,,, $uuid) = explode('/', $path_fragments['path']);
      $this->assertNotEmpty($uuid, 'There was a problem parsing the uuid from the signal.');

      $signal = $this->handler->resolveSignal($uuid);

      $this->assertInstanceOf('Acquia\Wip\Signal\SignalInterface', $signal);
      $this->assertEquals($wip_id, $signal->getObjectId());
      $this->assertEquals(\Acquia\Wip\Signal\SignalType::COMPLETE, $signal->getType());

      $found = TRUE;
      $this->handler->releaseCallback($uuid);
      try {
        $this->handler->resolveSignal($uuid);
      } catch (\RuntimeException $e) {
        $found = FALSE;
      }
      // We don't want the item to be found after release.
      $this->assertFalse($found);
    }

    /** @var SignalCallbackStoreEntry $record */
    foreach ($loaded as $record) {
      // Check that there is a matching local record for each one in the DB, and
      // it has the same WIP ID.
      $base_url = App::getApp()['config.global']['base_url'];
      $url = sprintf('%s/v1/signal/%s', $base_url, $record->getUuid());
      $this->assertTrue(isset($urls[$url]));
      $this->assertEquals($record->getWipId(), $urls[$url]);
      unset($urls[$url]);
    }
    // Should have used up all the local urls at this point.
    $this->assertEmpty($urls);
  }

  /**
   * Missing summary.
   *
   * @expectedException \RuntimeException
   */
  public function testBadWipId() {
    $this->handler->getCallbackUrl(-1);
  }

  /**
   * Missing summary.
   *
   * @expectedException \RuntimeException
   */
  public function testBadWipIdType() {
    $this->handler->getCallbackUrl(TRUE);
  }

  /**
   * Missing summary.
   *
   * @expectedException \RuntimeException
   */
  public function testBadType() {
    $wip_id = rand(1, 1000000);
    $this->handler->getCallbackUrl($wip_id, 'test');
  }

  /**
   * Missing summary.
   *
   * @expectedException \RuntimeException
   */
  public function testUuidNotFound() {
    $bytes = openssl_random_pseudo_bytes(64);
    $uuid = strtr(
      base64_encode($bytes),
      array(
        '+' => '-',
        '/' => '_',
        '=' => '',
      )
    );

    $this->handler->resolveSignal($uuid);
  }

  /**
   * Missing summary.
   */
  public function testCallUrl() {
    // Stub method. It hasn't been needed yet.
    $this->handler->callUrl('', new \stdClass());
  }

}
