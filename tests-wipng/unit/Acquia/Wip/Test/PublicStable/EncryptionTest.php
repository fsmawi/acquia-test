<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\Encryption;
use Acquia\Wip\Exception\EncryptionException;

/**
 * Test the encryption functionality.
 */
class EncryptionTest extends \PHPUnit_Framework_TestCase {

  /**
   * A sample pubkey used for testing the encryption and decryption end-to-end.
   */
  const PUBLIC_KEY = <<<EOT
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDWcST/FLE8BpNVDe4S03fKPmufzR9+A3UOWsjYRlAICwTen+x1eweA1MF5+VC8N+ZVyjR8pkCDX8lPSdj5ysXvapsTyzBxeemqp0mYsOmtUD1VS83d1I4C+pP+AD4/D9rlgJz90RwxEB52lxC1gelgb0Fsmmb9eZXyDVoRUNh5Ss9kDtd7DZwiy4PyaGmEKuwibeAesVBiu680z7OqEJ7ntVp+D0cDteCw3lxQubpTsksmuV4rFpGIGh4cM06gNAd69jpsQU4XDUnf0BxVJNET4w024Y8TXQDmBnK0JtQCp+EK0PjhZG3t9IuyPuvBi0uhRDrs9cNj2v1d8DTEetchmE3UqTmp8PIXZJpMm1svEkYE5z9XzIuM9qWBSrIeQNGjMnJcmvDLJFnH2UiZKFSX4b37HLHh0qpbTG1wIwrQX9L7xJVvG5zNvr/twUfHfqYO6D/pXIUkFHLQNyVxVc1bykCvODtVipgCfv6SQDAPSNptRdT3z2dmiKCDvEHD5iXmUg2FdaVz4ELuQS+NzuBIXqOSxwPt3wiSHUWgkjxFmVzcG6QGVT8m1apPKEdHjoZOGU7PmqADHVzP2RCpRqLReRpu2XvhTBLIhuhGvAkGbx7+gJdarbwHZaL0QjWzgOZLV0U2ZW5lWtVuRg0NyVFhNUbEyei/yiid0LG9ndBWOQ== wip-client@localhost

EOT;

  /**
   * A sample private key known only to the service for decrypting secure text.
   *
   * Uses 4096 bits to match what is used on the service.
   */
  const PRIVATE_KEY = <<<EOT
-----BEGIN RSA PRIVATE KEY-----
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
-----END RSA PRIVATE KEY-----

EOT;

  const UNENCRYPTED_STRING = 'My test string to encrypt/decrypt';

  const ENCRYPTED_NO_VERSION = '2acVuIYeuRTC7dOq5dwV5UzzfIlKiFiW4N6vBO99hEvUzQ29fXalsRaacvkaipRdr17nlfYA/T7RA51tMMXeaj5mjaeeXuGD/fbgRKHgR38/oTy3YFpilMPvWcBNkXCuTXUoLMd/G1BJYEGqF5qrAUidKO92TMv6yMWLoGtB4wOFdQv6KfEvqkaEs3k9z7XlAhQSdgfL2gQprHCcN5EnP8Qh3zCtRABxeRW2OItZaJITfLCPhfGrtZyV+qXNp9iOi4avAo5UcdRyuKy8gR/tKm/qJTM1krfFbBiquslrzbS5XPWGA0xpW8LwyW7mSaK5EqAUfzrfRM6NtBCL/+tLSSYJLqnMCzPlrGK+3se7eAPAs4wzDPGJua+AGb3CJ1sFxsDW74XY88PXb7tYhowYtYM9G2m5WMF0kX/KVW640IvfBO9qtNYY+8MoEZfLlq2VZMmze45mBDjgcnxkLU41adwd0lj4PX/ln00ez5blAAiFuhGxUyPHb2fY+InnG2icMunRg35GWUvglWmkS6qHWK1UDGUtsVFfq+ueIUXoDy4EphkWTZK/09h3np9r8qNi6kWQ5OnEavOWFiV2Hf2/aoHoad7KMYhSAOVQYoHxZ5zmo1hURoZiwn62+SlaoWtXfqaZvdcdkPXeuiM9uYVduGQYDfXcT5rDp/tAazduCxD7Zc=8g7nrnbDRxBw7JmvYBkpZDxqGkpf2MMDSTHCaW4JMqBuLdG4MzGSRNXbYqBlsADx';

  const ENCRYPTED_VERSION_1 = '2acX3LP3y9VFrKtMUBPqj0v7QH9Cyzt2txrJfBS6qjGAILQnmevU1xSMqLmiSjrjFMp9d02M4S7o8MMj6hPDJyXGPcXx4Orzmex7MkVS3NH5W3NCYebqh41NLqzEpoBsrCwewU7YBXWV+XbfHSHMFe5LXLTjV9K6UazPld9KGcHuAOmKvfLI28oV81wN67QahavW9C2d/kjKpLiBLvOpG1O6qwtidUJ0LrRVurH4NV8AaktKtZOIKq/dJdI+wx0+PZaCV5cmn09f7YTBl4AaFz/yLzy8IspAcccKR1OQPzFT4Ijq/RlIApxGROMN5BOfK3OG3lKEsGje3XMB2efz6EvrzBJdNR+zxnl6mjbFWXDfB1DmX/IhBO0MlpqSnDRIBKmYXie+MFBIGVU3nTuXhlyjq0KoACy3tKiN7C8TJQiZSZiOqZdDWG15E4h6u6NhLP+021YVzFZG6MWtR61/0XfqhEpbuFJsrMb+s0nLwwOEuXMQhHDpGrrhvXPu3OKH/neEhS9+Bvfqlf0uo0gjftvlXjGodlf3umgo+ZGz5kUleXcUqK4W+SeCHLcsVNbV0yiZo47BppCSENpz2+vLw4v9ih93E+zeE8yeEw9H0xJvw3sZ1DNxNNgpRGTZ5cYElkcxd1gl1ohlFJ+4R97NaXFEo2GBpUk4w7ziMBrWlQD+Ag=/3ilyVHziTo3e7ELqYVtLyyngFnkKcrGBxXtn5dwJpKaxoeABStzo0MJfZK4/1kUtfXKMwJBXj7vYvwYoU8rUf+G3a9BCSWOFxMMVIclbr83T1l7YyjHMlBLKUSTdcUtAP/p/SkbVcVflpFoX1tCj8yv2sL/ZtFwuI5EStpDdps=';

  const VALIDATION_STRING = '6ecf7ded-8051-4307-a3c1-5154f5767ebb';

  /**
   * Test that we cannot use a empty public key.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetPublicKey() {
    $encryption = new Encryption();
    $encryption->setPublicKey('');
  }

  /**
   * Test that we cannot use a empty public key.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetPrivateKey() {
    $encryption = new Encryption();
    $encryption->setPrivateKey('');
  }

  /**
   * Tests that we can encrypt and decrypt.
   */
  public function testEncryption() {
    $encryption = new Encryption();
    $encryption->setPrivateKey(self::PRIVATE_KEY);
    $encrypted_version_1 = $encryption->encrypt(self::UNENCRYPTED_STRING, 1, self::VALIDATION_STRING);
    $decrypted_version_1 = $encryption->decrypt($encrypted_version_1, 1, self::VALIDATION_STRING);
    $this->assertEquals(self::UNENCRYPTED_STRING, $decrypted_version_1);
  }

  /**
   * Tests that we can still predictably decode old strings.
   *
   * Since we use a random salt, the testEncryption() values will be different
   * each test run. This ensures that we are backwards compatible.
   */
  public function testDecryption() {
    $encryption = new Encryption();
    $encryption->setPrivateKey(self::PRIVATE_KEY);
    $decrypted_version_1 = $encryption->decrypt(self::ENCRYPTED_VERSION_1, 1, self::VALIDATION_STRING);
    $this->assertEquals(self::UNENCRYPTED_STRING, $decrypted_version_1);
  }

  /**
   * Tests that we cannot decrypt with a bad version 1 validation string.
   *
   * @expectedException \Acquia\Wip\Exception\EncryptionException
   */
  public function testCannotDecryptValidationStringMismatch() {
    $encryption = new Encryption();
    $encryption->setPrivateKey(self::PRIVATE_KEY);
    $decrypted_version_1 = $encryption->decrypt(self::ENCRYPTED_VERSION_1, 1, 'bad string');
  }

  /**
   * Tests that a no-version string cannot be decrypted by version 1 decrypt.
   *
   * @expectedException \Acquia\Wip\Exception\EncryptionException
   */
  public function testCannotDecryptVersionMismatch() {
    $encryption = new Encryption();
    $encryption->setPrivateKey(self::PRIVATE_KEY);
    $decrypted_no_version = $encryption->decrypt(self::ENCRYPTED_NO_VERSION, 0, NULL);
  }

  /**
   * Tests that we can determine the encryption version from an encrypted message.
   */
  public function testDetermineVersion() {
    $encryption = new Encryption();
    $encryption->setPrivateKey(self::PRIVATE_KEY);
    $this->assertEquals(0, $encryption->determineVersion(self::ENCRYPTED_NO_VERSION));
    $this->assertEquals(1, $encryption->determineVersion(self::ENCRYPTED_VERSION_1));
  }

}
