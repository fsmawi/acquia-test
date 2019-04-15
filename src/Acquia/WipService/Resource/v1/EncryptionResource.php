<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\App;
use Acquia\WipService\Exception\InternalServerErrorException;
use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Acquia\WipService\Validator\Constraints\JsonDecode;
use Acquia\Wip\Encryption;
use Acquia\Wip\Environment;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\Ssh\SshKeys;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Teapot\StatusCode;

/**
 * Provides REST API endpoints for interacting with encryption.
 */
class EncryptionResource extends AbstractResource {

  /**
   * The name of the encoding key.
   */
  const ENCODING_KEY_NAME = 'buildsteps.key';

  /**
   * The version of the encryption scheme to use.
   */
  const VARIABLE_ENCRYPTION_VERSION = 1;

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array();
  }

  /**
   * Encrypts a value to be used in a build yaml file.
   *
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   * @param Application $app
   *   An instance of Application representing the application.
   *
   * @return HalResponse
   *   An instance of HalResponse representing the HTTP response.
   *
   * @throws RuntimeException
   *   If the keys cannot be loaded or the encrypted value cannot be validated.
   */
  public function postEncryptAction(Request $request, Application $app) {
    $environment = Environment::getRuntimeEnvironment();
    $keys = new SshKeys();
    $keys->setRelativeKeyPath(self::ENCODING_KEY_NAME);

    // If there is no encryption key, create one now.
    if (!$keys->hasKey($environment)) {
      $keys->createKey($environment, NULL, 'BuildSteps encryption key');
    }

    $json_body = $request->getContent();
    $this->validate(new JsonDecode(
      'Malformed request entity. The message payload was empty or could not be decoded.'
    ), $json_body);
    $request_body = json_decode($json_body, TRUE);

    $public_key = file_get_contents($keys->getPublicKeyPath($environment));
    $private_key = file_get_contents($keys->getPrivateKeyPath($environment));

    if (empty($public_key) || empty($private_key)) {
      throw new \RuntimeException('Unable to locate encryption keys.');
    }

    $encryption = new Encryption();
    $encryption->setPublicKey($public_key);
    $encryption->setPrivateKey($private_key);
    $encrypted_value = $encryption->encrypt(
      $request_body['data-item'],
      self::VARIABLE_ENCRYPTION_VERSION,
      $request_body['group-id']
    );

    // Test that the encrypted value can be decrypted.
    $decrypted_value = $encryption->decrypt(
      $encrypted_value,
      self::VARIABLE_ENCRYPTION_VERSION,
      $request_body['group-id']
    );
    if ($request_body['data-item'] != $decrypted_value) {
      throw new \RuntimeException('Unable to verify the encrypted value.');
    }

    $response = array(
      'encrypted_value' => $encrypted_value,
    );
    $hal = $app['hal']($request->getUri(), $response);
    return new HalResponse($hal);
  }

}
