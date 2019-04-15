<?php

namespace Acquia\Wip;

use Acquia\Wip\Exception\EncryptionException;

/**
 * Encrypts confidential data using a standard hybrid encryption algorithm.
 */
class Encryption {

  /**
   * This class uses a versioning scheme to encrypt/decrypt, @see MS-2043.
   *
   * If no version is specified, the secret is encrypted as a string. For
   * version 1, it is encrypted as a JSON blob, e.g.
   *
   * {"version":1,"group-id":"your-group-id","value":"your-encrypted-value"}
   *
   * Where version is the encryption version, value is the secret value,
   * group-id is a custom string that is used to authorize decryption - the
   * value passed to encrypt() and decrypt() must match.
   */

  const CIPHER_METHOD = 'aes-256-cbc';

  /**
   * The public key.
   *
   * @var string
   */
  private $publicKey;

  /**
   * The private key.
   *
   * @var string
   */
  private $privateKey;

  /**
   * Sets the public key.
   *
   * @param string $public_key
   *   The public key.
   */
  public function setPublicKey($public_key) {
    if (!is_string($public_key) || empty($public_key)) {
      throw new \InvalidArgumentException('The "public_key" argument must be a non-empty string.');
    }
    $this->publicKey = $public_key;
  }

  /**
   * Sets the private key.
   *
   * @param string $private_key
   *   The private key.
   */
  public function setPrivateKey($private_key) {
    if (!is_string($private_key) || empty($private_key)) {
      throw new \InvalidArgumentException('The "private_key" argument must be a non-empty string.');
    }
    $this->privateKey = $private_key;
    $this->setPublicKey(openssl_pkey_get_details(openssl_pkey_get_private($this->privateKey))['key']);
  }

  /**
   * Encrypts an input string.
   *
   * @param string $message
   *   The message to encrypt.
   * @param int $version
   *   Optional. The version of the encryption scheme to use.
   * @param string $validation_string
   *   Optional. A string to validate a version 1 encryption.
   *
   * @return string
   *   The encrypted message.
   *
   * @throws \RuntimeException
   *   If the public key is not set.
   */
  public function encrypt($message, $version = 1, $validation_string = '') {
    if (empty($this->privateKey)) {
      // We need the private key to extract the public key in the right format.
      throw new \RuntimeException('A private key must be set to encrypt values.');
    }

    // The symmetric key is different per value.
    $symmetric_key = openssl_random_pseudo_bytes(150);
    $ivsize = openssl_cipher_iv_length(self::CIPHER_METHOD);
    $iv = openssl_random_pseudo_bytes($ivsize);

    // Symmetric encryption is used to encode the confidential data.
    $encrypted_value = '';
    if ($version == 1) {
      $unencrypted_object = new \stdClass();
      $unencrypted_object->version = 1;
      $unencrypted_object->group_id = $validation_string;
      $unencrypted_object->value = $message;
      $message = json_encode($unencrypted_object);
    }

    $ciphertext = openssl_encrypt(
      $message,
      self::CIPHER_METHOD,
      $symmetric_key,
      OPENSSL_RAW_DATA,
      $iv
    );

    $encrypted_value = $iv . $ciphertext;

    // The symmetric key is encrypted using the public key. The private key is
    // secret to the WIP hosting site and is therefore asymmetric in nature.
    $encrypted_key = '';
    openssl_public_encrypt($symmetric_key, $encrypted_key, openssl_pkey_get_public($this->publicKey), OPENSSL_PKCS1_PADDING);

    // The encrypted symmetric key and encrypted confidential data are base64
    // encoded to avoid problems with UTF characters.
    $encrypted_key = base64_encode($encrypted_key);
    $encrypted_value = base64_encode($encrypted_value);

    // The length of the encoded symmetric key in hex is determined.
    $key_length = dechex(strlen($encrypted_key));
    $key_length = str_pad($key_length, 3, '0', STR_PAD_LEFT);

    // Finally, we concatenate:
    // - The length of the symmetric key in hex, after it has been encrypted
    //   using the public key and base64-encoded.
    // - The symmetric key itself, encrypted by the public key and
    //   base64-encoded.
    // - The confidential data, encrypted by the symmetric key.
    $encrypted_message = $key_length . $encrypted_key . $encrypted_value;

    return $encrypted_message;
  }

  /**
   * Extracts the version from the encrypted value.
   *
   * @param string $encrypted_message
   *   The whole encrypted string from the build document.
   *
   * @return int
   *   The encryption version.
   */
  public function determineVersion($encrypted_message) {
    $version = 0;
    $decrypted_string = $this->decryptMessage($encrypted_message);
    $decoded_value = json_decode($decrypted_string);
    if (json_last_error() == JSON_ERROR_NONE && !empty($decoded_value->version)) {
      $version = $decoded_value->version;
    }
    return $version;
  }

  /**
   * Decodes a single value from a previously encrypted message.
   *
   * For the sake of security, the customer can use a different symmetric key
   * for every value, that way even if two sensitive data values are the same,
   * their encrypted values will be different. To support this flexibility, the
   * encrypted values will contain all the data necessary for decrypting.
   *
   * The format of the encrypted values in the build document is:
   *
   * {encoded_symmetric_key_len}{encrypted_symmetric_key}{encrypted_message}
   *
   * - encoded_symmetric_key_len: The length of the encoded symmetric key
   *   appears in the first 3 characters in hex format (the max value of which
   *   is fff = 4095). The length is necessary to be able to get the other two
   *   components, so it comes first.
   * - encrypted_symmetric_key: The symmetric key itself (encoded by the
   *   asymmetric key).
   * - encrypted_message: The encoded value (encoded by the symmetric key).
   *
   * The encoded symmetric key and encoded message are base64 encoded so we
   * don't need to worry about UTF characters.
   *
   * @param string $encrypted_message
   *   The whole encrypted string from the build document.
   * @param int $version
   *   Optional. The version of the encryption scheme to use.
   * @param string $validation_string
   *   Optional. A string to validate a version 1 decryption.
   *
   * @return string
   *   The decrypted value if the decoding succeeded.
   *
   * @throws \RuntimeException
   *   If the private key is not set.
   * @throws EncryptionException
   *   If the decryption is not authorized or out of date.
   */
  public function decrypt($encrypted_message, $version = 1, $validation_string = '') {
    if (empty($this->privateKey)) {
      throw new \RuntimeException('A private key must be set to decrypt values.');
    }
    $decrypted_value = NULL;
    $decrypted_string = $this->decryptMessage($encrypted_message);


    // Validate the decrypted value based on the version.
    $decoded_value = json_decode($decrypted_string);
    // The old format is a string, the new is a JSON blob.
    if (json_last_error() == JSON_ERROR_NONE && !empty($decoded_value->version)) {
      switch ($decoded_value->version) {
        case 1:
          // Version 1 simply matches an application id from a build with
          // the group-id specified in the encrypted blob.
          if ($decoded_value->group_id == $validation_string) {
            $decrypted_value = $decoded_value->value;
          } else {
            throw new EncryptionException(EncryptionException::TYPE_DECRYPTION_NOT_AUTHORIZED);
          }
          break;

        default:
          throw new EncryptionException(EncryptionException::TYPE_DECRYPTION_ERROR);
      }
    } else {
      throw new EncryptionException(EncryptionException::TYPE_DEPRECATED_VERSION);
    }

    return $decrypted_value;
  }

  /**
   * Decrypts the message which could be a string or a JSON blob.
   *
   * @param string $encrypted_message
   *   The whole encrypted string from the build document.
   *
   * @return string|null
   *   The decrypted message or null if unable to decrypt.
   */
  protected function decryptMessage($encrypted_message) {
    $decrypted_string = NULL;

    // Split the compound string into its constituent parts.
    $sym_key_length = hexdec(substr($encrypted_message, 0, 3));
    $encrypted_sym_key = substr($encrypted_message, 3, $sym_key_length);
    $encrypted_value = substr($encrypted_message, 3 + $sym_key_length);

    // Base64 decode the symmetric key and encrypted value.
    $encrypted_sym_key = base64_decode($encrypted_sym_key);
    $encrypted_value = base64_decode($encrypted_value);

    // Decrypt the symmetric key using the private key.
    $symmetric_key = NULL;
    if ($encrypted_sym_key) {
      openssl_private_decrypt($encrypted_sym_key, $symmetric_key, openssl_pkey_get_private($this->privateKey), OPENSSL_PKCS1_PADDING);
    }

    // Decrypt the encrypted value using the symmetric key.
    if (!empty($symmetric_key)) {
      $ivsize = openssl_cipher_iv_length(self::CIPHER_METHOD);
      $iv = mb_substr($encrypted_value, 0, $ivsize, '8bit');
      $ciphertext = mb_substr($encrypted_value, $ivsize, NULL, '8bit');
      $decrypted_string = openssl_decrypt(
        $ciphertext,
        self::CIPHER_METHOD,
        $symmetric_key,
        OPENSSL_RAW_DATA,
        $iv
      );
    }

    return $decrypted_string;
  }

}
