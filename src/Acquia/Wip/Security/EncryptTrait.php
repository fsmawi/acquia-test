<?php

namespace Acquia\Wip\Security;

/**
 * This trait provides a simple means of encrypting and decrypting strings.
 *
 * This code has been adapted from
 * https://secure.php.net/manual/en/function.mcrypt-encrypt.php.
 */
trait EncryptTrait {

  /**
   * Effectively a constant that indicates the OPENSSL encryption method.
   *
   * @var string
   */
  private static $encryptOpenSslMethod = 'aes-256-ctr';

  /**
   * The encryption strategy being employed.
   *
   * This field is only initialized on new instances, and thus provides a means
   * of knowing whether mcrypt or openssl should be used for decryption. This
   * is for backward compatibility.
   *
   * @var string
   */
  private $strategy = 'openssl';

  /**
   * The key used to encrypt and decrypt.
   *
   * @var string
   */
  private $key = NULL;

  /**
   * Encrypts the specified text.
   *
   * @param string $plaintext
   *   The text to encrypt.
   *
   * @return string
   *   The encrypted string.
   */
  protected function encrypt($plaintext) {
    if ($this->usingOpenSsl()) {
      $result = $this->openSslEncrypt($plaintext);
    } else {
      $result = $this->mcryptEncrypt($plaintext);
    }
    return $result;
  }

  /**
   * Encrypts using the OpenSSL encryption strategy.
   *
   * @param string $plaintext
   *   The message to encrypt.
   *
   * @return string
   *   The encrypted message.
   */
  private function openSslEncrypt($plaintext) {
    if (empty($plaintext)) {
      $result = '';
    } else {
      $key = $this->openSslGetKey();
      $nonce_size = openssl_cipher_iv_length(self::$encryptOpenSslMethod);
      $nonce = openssl_random_pseudo_bytes($nonce_size);

      $cipher_text = openssl_encrypt(
        $plaintext,
        self::$encryptOpenSslMethod,
        $key,
        OPENSSL_RAW_DATA,
        $nonce
      );
      // @todo - handle the failure - openssl_encrypt can return FALSE.

      // Pack the IV and the cipher text together.
      $result = base64_encode($nonce . $cipher_text);
    }
    return $result;
  }

  /**
   * Encrypts using the mcrypt encryption strategy.
   *
   * @param string $plaintext
   *   The message to encrypt.
   *
   * @return string
   *   The encrypted message.
   */
  private function mcryptEncrypt($plaintext) {
    if (empty($plaintext)) {
      return '';
    }

    // Create a random IV to use with CBC encoding.
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

    // Creates a cipher text compatible with AES (Rijndael block size = 128)
    // to keep the text confidential. Only suitable for encoded input that
    // never ends with value 00h (because of default zero padding).
    $key = $this->getKey();
    $encrypted = mcrypt_encrypt(
      MCRYPT_RIJNDAEL_128,
      $key,
      $plaintext,
      MCRYPT_MODE_CBC,
      $iv
    );

    // Prepend the IV for it to be available for decryption.
    $annotated = $iv . $encrypted;

    // Encode the resulting cipher text so it can be represented by a string.
    return base64_encode($annotated);
  }

  /**
   * Decrypts the specified text.
   *
   * @param string $encrypted_value
   *   The encrypted text.
   *
   * @return string
   *   The decrypted text.
   *
   * @throws \Exception
   *   If the value could not be decrypted.
   */
  protected function decrypt($encrypted_value) {
    if ($this->usingOpenSsl()) {
      $result = $this->openSslDecrypt($encrypted_value);
    } else {
      $result = $this->mcryptDecrypt($encrypted_value);
    }
    return $result;
  }

  /**
   * Decrypts the specified message using the OpenSSL decryption strategy.
   *
   * @param string $encrypted_value
   *   The encrypted message.
   *
   * @return string
   *   The decrypted message.
   *
   * @throws \Exception
   *   If the message could not be decrypted.
   */
  private function openSslDecrypt($encrypted_value) {
    $key = $this->openSslGetKey();
    $encrypted_value = base64_decode($encrypted_value, TRUE);
    if ($encrypted_value === FALSE) {
      throw new \Exception('Decryption failure');
    }

    $nonce_size = openssl_cipher_iv_length(self::$encryptOpenSslMethod);
    $nonce = mb_substr($encrypted_value, 0, $nonce_size, '8bit');
    $cipher_text = mb_substr($encrypted_value, $nonce_size, NULL, '8bit');

    $result = openssl_decrypt(
      $cipher_text,
      self::$encryptOpenSslMethod,
      $key,
      OPENSSL_RAW_DATA,
      $nonce
    );
    // @todo - Handle the failure case - openssl_decrypt can return NULL.

    return $result;
  }

  /**
   * Decrypts the specified value using the mcrypt strategy.
   *
   * @param string $encrypted_value
   *   The message to be decrypted.
   *
   * @return string
   *   The decrypted message.
   */
  private function mcryptDecrypt($encrypted_value) {
    if (empty($encrypted_value)) {
      return '';
    }
    $decoded = base64_decode($encrypted_value);

    // Retrieves the IV, iv_size should be created using mcrypt_get_iv_size().
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv_dec = substr($decoded, 0, $iv_size);

    // Retrieves the cipher text (everything except the $iv_size in the front).
    $decoded = substr($decoded, $iv_size);

    $key = $this->getKey();
    // May remove 00h valued characters from end of plain text.
    $result = mcrypt_decrypt(
      MCRYPT_RIJNDAEL_128,
      $key,
      $decoded,
      MCRYPT_MODE_CBC,
      $iv_dec
    );
    return trim($result);
  }

  /**
   * Gets the encryption key.
   *
   * If the key has not been generated yet, it will be generated.
   *
   * @return string
   *   The encryption key.
   */
  private function getKey() {
    if ($this->usingOpenSsl()) {
      $result = $this->openSslGetKey();
    } else {
      $result = $this->mcryptGetKey();
    }
    return $result;
  }

  /**
   * Gets the encryption key suitable for the OpenSSL strategy.
   *
   * If the key has not been generated yet, it will be generated.
   *
   * @return string
   *   The encryption key.
   */
  private function openSslGetKey() {
    if (empty($this->key)) {
      $this->key = base64_encode($this->generateRandomKey(64));
    }
    return hex2bin(base64_decode($this->key));
  }

  /**
   * Gets the encryption key suitable for the mcrypt strategy.
   *
   * If the key has not been generated yet, it will be generated.
   *
   * @return string
   *   The encryption key.
   */
  private function mcryptGetKey() {
    if (empty($this->key)) {
      $this->key = $this->generateRandomKey();
    }
    return hex2bin($this->key);
  }

  /**
   * Generates a random key of the specified size.
   *
   * @param int $size
   *   Optional. Controls the size of the resulting key.
   *
   * @return string
   *   The key.
   */
  private function generateRandomKey($size = 32) {
    $result = '';
    $iterations = ceil($size / 40);
    for ($i = 0; $i < $iterations; $i++) {
      $result .= openssl_digest(openssl_random_pseudo_bytes(10), 'sha256');
    }
    return substr($result, 0, $size);
  }

  /**
   * Indicates whether the OpenSSL strategy is being employed.
   *
   * @return bool
   *   TRUE if OpenSSL is being used; FALSE otherwise.
   */
  private function usingOpenSsl() {
    return $this->strategy === 'openssl';
  }

}
