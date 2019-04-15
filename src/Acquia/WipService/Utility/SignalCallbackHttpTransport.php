<?php

namespace Acquia\WipService\Utility;

use Acquia\WipIntegrations\DoctrineORM\Entities\SignalCallbackStoreEntry;
use Acquia\WipIntegrations\DoctrineORM\SignalCallbackStore;
use Acquia\WipService\App;
use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalCallbackHttpTransportInterface;
use Acquia\Wip\Signal\SignalType;
use Silex\Application;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Defines an HTTP signal callback.
 */
class SignalCallbackHttpTransport implements SignalCallbackHttpTransportInterface {

  /**
   * The signal storage instance.
   *
   * @var SignalCallbackStore
   */
  private $storage;

  /**
   * {@inheritdoc}
   */
  public function getCallbackUrl($wip_id, $type = SignalType::COMPLETE) {
    if (!is_int($wip_id) || $wip_id < 0) {
      throw new \RuntimeException('WIP ID argument must be a non-negative integer.');
    }
    if (!SignalType::isLegal($type)) {
      throw new \RuntimeException(sprintf(
        'Type %s is not a valid SignalType.',
        var_export($type, TRUE)
      ));
    }

    $storage = $this->getStorage();
    if ($wip_id === 0) {
      // This is a special system callback. There should only be one. This
      // callback will be used forever, so we don't need to allocate a new one
      // for each asynchronous call.
      $callback_entries = $storage->loadByWipId($wip_id);
      foreach ($callback_entries as $callback_entry) {
        $uuid = $callback_entry->getUuid();
        break;
      }
    }

    if (empty($uuid)) {
      $uuid = $this->createUuid();
      $storage->insert($uuid, $wip_id, $type);
    }

    /** @var Application $app */
    $app = App::getApp();
    /** @var UrlGenerator $generator */
    $generator = $app['url_generator'];
    $context = $generator->getContext();
    $context->setBaseUrl($app['config.global']['base_url']);
    $generator->setContext($context);
    return $generator->generate('PostSignalV1', array('id' => $uuid));
  }

  /**
   * Generates a UUID.
   *
   * @return string
   *   The newly generated UUID.
   */
  private function createUuid() {
    // 256 bits.
    $bytes = openssl_random_pseudo_bytes(64);
    // URL-safe replacements, borrowed from Drupal.
    $result = strtr(base64_encode($bytes), array(
      '+' => '-',
      '/' => '_',
      '=' => '',
    ));

    // This theoretically can never happen, but it's bad if it does - we can't
    // proceed if this happens.
    if (strlen($result) < 32) {
      // @codeCoverageIgnoreStart
      // I can't produce this effect in testing.
      throw new \RuntimeException('UUID too short.');
      // @codeCoverageIgnoreEnd
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveSignal($uuid) {
    /** @var SignalCallbackStoreEntry $callback_entry */
    $callback_entry = $this->getStorage()->load($uuid);

    if (empty($callback_entry)) {
      throw new \RuntimeException(sprintf(
        'Unable to locate stored callback object for %s',
        $uuid
      ));
    }

    $signal = new Signal();
    $signal->setObjectId($callback_entry->getWipId());
    $signal->setType($callback_entry->getType());
    return $signal;
  }

  /**
   * {@inheritdoc}
   */
  public function callUrl($url, $data) {
  }

  /**
   * Returns the storage object for signal callback uuids.
   *
   * @return SignalCallbackStore
   *   The signal callback storage instance.
   */
  public function getStorage() {
    // *This* class is the concrete implementation of
    // SignalCallbackHttpTransportInterface, which makes the storage an
    // implementation detail, so we don't need to further abstract that storage
    // layer in this case.
    if (!isset($this->storage)) {
      $this->storage = new SignalCallbackStore();
    }
    return $this->storage;
  }

  /**
   * {@inheritdoc}
   */
  public function releaseCallback($uuid) {
    /** @var SignalCallbackStoreEntry $callback_entry */
    $callback_entry = $this->getStorage()->load($uuid);
    if (!empty($callback_entry)) {
      $this->getStorage()->delete($callback_entry);
    }
    // If there is no record to delete, assume that we already deleted it - this
    // need not be a fatal error.
  }

}
