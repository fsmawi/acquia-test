<?php

namespace Acquia\Wip\Test\PrivateUnstable\Objects\Modules;

use Acquia\Wip\AcquiaCloud\CloudCredentials;
use Acquia\Wip\Iterators\BasicIterator\WipContext;
use Acquia\Wip\Modules\NativeModule\BuildSteps;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;
use Acquia\Wip\WipFactory;

/**
 * Tests the BuildSteps Wip object.
 */
class BuildStepsTest extends \PHPUnit_Framework_TestCase {

  /**
   * RSA key with no password.
   *
   * @var string
   */
  private $noPasswordKeyRsa = <<<EOT
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEAwIJydFuYv9kUZ/Qx6+4RwwVepiCMIGdituLkPHyzHnVkaAsW
QRIXtqlJdIKB/s+TKX27Ki4vr+tI7H93cv+2kyEMS+dly7iSrkMhGaouTdKRPTL5
s19yW/MOcFlFpw/n7lc20SprcbQbknfRcGaeofOBS7BeGl3gPbIhOwXO/AqNqDMI
BCBrYOj1ikfSgyUeAcRgfjwPMCmztzgGgNgWuezmdd4FFvm/2p5jiacGabZR7tpR
iftXDMsMm3vK9fg353tM02YvRQH8bxGjEXAPNaA+nx5jQmSyfnU8O68hk/9pC3wM
lB23H1NNXJi7XeXerfQUXl2rcJ5BLFqne8cHXQIDAQABAoIBAHJfzlJ1bS5mcepF
Oje7LRBaK162Laq+4fZYnVOWnvD0vB/YnnMwpagfsgWn2EYk24EmM0IfSLPTLXNd
VAGeDcIiO2UJaQB9e6BPP+Y4puQTu7jJhXNdvNsGcEitsWVNXPXNGUNyWX2njU4b
I/MM9SixPNtUQMSXi7f8EiQ4Ej7JmbNrRCN4MwovOQelRSHC9+wW+RhopcEcJpMO
PfQcbfuk7436Io5Xb0a6QaHy7ym5nV1e/Btp9HVawo3JlCQ1t8btG4VQoQoJR9P+
CQUKaAZa0nvtp65xf0+5oaTG9Z1v67A5j8lc0CdBC5qfxP5S6dsRtjoqrd907JAH
RhGwNL0CgYEA8Ku3s9WE8kbPQp/z+WZo4ny6xTWnO09P6jV9zScv/YI8uzpDlioS
/B2gCAjSTMqhw0ZPPAWF8P/0ypUyOgEhV1Gij0O0mxfp8XgX8P+aC/TTxrQLLP1G
vHsa8c4ACcnLc6EiTNj6ZmCs52TP6e7rANM7RmJ+Ecz22WcWbBuWMosCgYEAzMVu
iz2TY63RzYmJ/70Q2eySBIknRZ9kto6aGZDQ6D3vR0E/FKGvyF9TmlmEV37zeq84
jhNTsNykjbjqR3w6HBNl+Bs/bRd0U5gLOUWThahibP/RQBeL5FhpB2Ig0eXfWQPU
S45fkBJv3KaBWMe4N6v6e/H+A7plsNyxfyKVcrcCgYEA7y4I3C0n+cuLcTAbvFEd
jXDeAN2ofBX/WsicZIU8eVm0V3G494SK54ndn/58WZrLlpDKb+EhUvhc4/PQPbsf
0nKr9msYE0Z51eM/D+BFzPoceY42yRhQ80H47jSG7zNgAWMy/mJov9P2IeSbiGZL
oL9MWk/J8JDdBgQgUYI9C68CgYEAqULZKUdwuYhIT/lMlJQXhctCt3UXfTlP6obd
YhyOUio/y0pndgpgXuRNGty7xAcwA00rFmVrXFpFutAK96P79JEkTH1ZZDdq9F5N
iL22P1j8YtTihnPwMoPR7URzlIzKna3IodvBqjlTNbR8XoJYB8ykdCeHrFU7EYKb
RVe06OkCgYBuxD9gzeaqhrcpHi85URP0eNYvURYM3bbS2fLgpCv62NU2nRjntNFy
/F4SdMYACeYIRhMYaGWaj3k+1/GFs58IzddUkqTaEgxFfmjRFLHrpG3+vzIeJFoq
bhImnv22TiVW8LT6ybtui966pehjLgvuiTXXrbT1zj0RMLCap9217Q==
-----END RSA PRIVATE KEY-----
EOT;

  /**
   * DSA key with no password.
   *
   * @var string
   */
  private $noPasswordKeyDsa = <<<EOT
-----BEGIN DSA PRIVATE KEY-----
MIIBuwIBAAKBgQDNwumSlhCkeZ7K/QhfvJAVka5u+Q0QDYHQKecqQ/F0oIySuVF0
X2Nt1c7t/uoelLv7kukSOfiZgS01vNozex0ZSsJKE8j8gGsaxbGBUM6PmwFLtSse
Fr+sPJz6DI/rr7xoAmNfL/u92Akv8tmMYvWV/Hl+dkOz9Tk8Q6Y1M59HuwIVAIe8
bz30u/mjiO1GC/9S3hfDWDgJAoGAWL8Fb3sTxHOWVkEFEiPkUhsUDKkeIqfgmY07
YBAsByfhEnAuECnBWXGPUj/UCeIx3QoXq5IrJku87tKFMjrTcKiTuZjVOuuKFSdb
vVPMjyp1POZPPGk7lgy/0HN7sRs85YOGA2cA9Yeb95j76S6HL2ZL2WrbWDb1nUMz
mQDLJIUCgYEAk62Q/TdzBI1yjIwl0ohD8wGUbJWIXX4Q5KBGZuN60ZrCQmrN9DlV
RJLj+cTuBo6XF/DsGF6lnfm1k5pdJmAlzOX5qIvNvIQWm/DJJY0mIaJz7MW9J1j1
qicKeDuGskL9UmaJDUXY41U8Plyti6jX9p2LlSwypcpQUUES3LbHligCFBSa/58r
Tjtani+8NHTs77IMzmBa
-----END DSA PRIVATE KEY-----
EOT;

  /**
   * ECDSA key with no password.
   *
   * @var string
   */
  private $noPasswordKeyEcdsa = <<<EOT
-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIMxvU26WD/w7A99BNx0p4ismzaWoOGziLmfWFC8U1g+IoAoGCCqGSM49
AwEHoUQDQgAE10ZCwa2hBs7hauPGt8JT6PElHeJjzlsUG5x1nCAjUUXwFgN6Hl3T
i0asu3vNkRTqOvYA8xU8Re9Qj02I5jcwFQ==
-----END EC PRIVATE KEY-----
EOT;

  /**
   * RSA1 key with no password.
   *
   * @var string
   */
  private $noPasswordRsa1 = <<<EOT
U1NIIFBSSVZBVEUgS0VZIEZJTEUgRk9STUFUIDEuMQoAAAAAAAAAAAgACACyIXUmvdmzVAwnHYt5MwNrIMzw0uxp4SPfdZe6ouIw3dAlKDGgYvrrJ/qq6qoHcowWot+6mhhpw2sSqGbDvdhK1t2CykT7RouBkLncOn7fobEI5+xJQ/Zi83ZdEZ3lkH8xr5bEf3C/j0h984pe8B5j2e8cyKKdFcJoRikHn3E8Jrx/hsq4Ct5VUDQm8UDSqHCmAFTH8btm4tgfGTvRN7jEEId0Qzby2mTk4uHdCgtiQXlo/UpGP6djPZE9MPrZOtgINyuxoHUqaf+qJSn0NmU6SCT2MlkZqz+R5atLGMYAdGKcpul3rA2CI1eXUz/4Sa5G8w1zfIzsG4/iIyQzqactABEBAAEAAAAnbWFyYy5pbmdyYW1AbWFjYm9va3Byby1tYXJjaW5ncmFtLmxvY2Fsdw13DQf+O6FtqVl2rkydgN6oGRuVsmAZmyjQ0aT3sE+aSnVKiRdIHZg1/L4Fx4Drrhm+b53N/anqgXoiYA12I5nSRQahiHQjVSxHKWL1QptjTLEcVdq3/AY/mEszCO79maxTEk1r9wMT0pXi9Y2Os/M6SAvT8JM9M4KKxVFKckPCanXan0q9V4t1XdKZ5b9VwKnn5t+joGnF2iYCH9POBHuUD1INm8PayIf7n2KM1T5d/fgJ7d2FrDw+Plf2MFv/Ih2dKNy3fAnrlQNPxPNPGjlas2zHrglSkkgz4cfZNhITkWpkuUuUEi83u0MNtySbxsMkyFvS+MvafnbMPbro7aTU4mUWFQQAv4dfe9yvho0jFGn3ivz5SQ92hF8h1vDa6UfWJ8HY7PhyBs2k06e7FMUQQuKPhBXpZdPDhH7+/Mh9EEQJMwebLNoV1YtZlAcyto099UjZRu4xG1LyEEa2PZiAXAvOvPdmXPf4FdPgO0tcw43JQjdiiIFL3KCV+yXTuzO7H8/8g4YEAM82a7OShbWufQrPRlyBsrPoCQej2tfaVFuWZi78K+KFN8BSrIcTNajVpszBSGG4AENFylnVKkmvNhVnSzINo55aB4O8ZdygWoaGqPmuUm7q5FSWU3zWZMrI1g1I5T4lXJpb7BxO42bq4rlCCAy1rskLDxuhFfTV8Ygc8LwhVqsvBADcEiYDbFOI9hB8h1yQmISRdlDAKRLWLbnKd0teAz3KoCWWLSqt96HiiJ89j1ixMUeldbuZAE2LcNynXsZMh5BER0lj2TCxEFrUQ9xIBXw24BH/W3xZ5mswfUTyjexHpYGx5fqMcGKzkGwkzJWAEcmo6T4v5U/10LLTo8HVDgTMYwAAAAA=
EOT;

  /**
   * RSA key with password.
   *
   * @var string
   */
  private $passwordKeyRsa = <<<EOT
-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: AES-128-CBC,1A32230E42EB48836F468F6850FF6E8E

2gD0tY6UY7xDy8cU08vPyzm8yjDxmDRov/5Req5wytQQTv4Xpvbmgssg5VpgCveS
X+UFsScZ3oKRkTDgBYdioTxHn197FdRfQcy8bI4j3F9j8VKHDHpCqvGco822bYvA
hERt1VnGD1WiSoUsvppz58CC/MzWRzl2gPaNoZktlKCNbG4Q/dzUJb0K0s/y4ye1
S22yOLcAde8n1m4ESQ7STpbnEpixG+qHMZ7z2KUhd/TVu+iZH7IBlWyI8QbnLvQR
qPUXH7vidDjntri16/NOOwavhE/gG4S3bhIdq3U3Nzrm4MT39LgaH4aWzW1eh4DO
P5ikXoGSv2y2ti9ysJGn/3rCairO4IuPOmwXcmCjJ3OoT2zVvM5WYPVvqnVZXonb
jVn1QImr3oUlLawLm7iSCmU+KzmdEKz/CLcypSLq61tKCLL7BXI+syZTC1ox21zT
R0mwKgHEXGPG00zHg+So2glvFEgDJ4hVSaFEb3iK6o0GVoQfOmmNDUzuhEfdVOon
xfTXlBk9M4oLVdVDuZFQ7uIbobDeH9/8XxsKaabzdqjgw6BM7OVAsNmFnBQFo8By
sbQGAVYgryXoqldNqbDtmeC4BV76VfO3HSfaQHUYRNo=
-----END RSA PRIVATE KEY-----
EOT;

  /**
   * DSA key with password.
   *
   * @var string
   */
  private $passwordKeyDsa = <<<EOT
-----BEGIN DSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: AES-128-CBC,742965763B905537315FE8677F88C177

85OvvCQfyzYMIhyxv96teHOhXwdbQR5GVH6KpwBUCX97lQ44L2WB8/xP+nqEvUPW
W/iEJbIaMFQapxWPRomi/sotX25MzUbD2hW1O5wxO5C+04Rhf0xu/aE+UErxy1Z0
wuSjbYOg1pxg7Oj5sN9CRwIT1vcRa85pJiCyDBvSbj/9zJpPENgTSGhWYYyiPClO
ur3aAaZL/lK+J/WUfzhaWD1mudf7AqJ3q0VkLqJagLlvzM7ANQYOBN2Rv54twYJS
jTgeZt0SZ3ZoNeS1TIxx7ua5TDGzcCz6/5pgZJaLl5IBleO845t9kZubp3PEGhZH
bNPvT8KeXc5ZJRdcKnEKPEsmYtS7fIWU3XfybdMLmk1C3LAsF4m8uqSqNd4wIgy3
urjCACgK767wwvC6vpXqf0O9+cR2/OoOSNZzCFm4duZBYaLevytnfjkdU5ERK/aD
ESxVtDK8YXATF2gqoXS1HQNnAj01swMe31r0hlfwbf6WJhA/jY/KhHQDpcIWQkkp
yKtLWQ2ewjgtHGK+bFxR6KRACq5VVopq2QD7Sl4ZXRmJc9TninwVguNYlz7L8Yvd
rzdqa2vZGVgYsYt53nrvKA==
-----END DSA PRIVATE KEY-----
EOT;

  /**
   * ECDSA key with password.
   *
   * @var string
   */
  private $passwordKeyEcdsa = <<<EOT
-----BEGIN EC PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: AES-128-CBC,7963C94CDE0847AD8C70F7F476B3360B

RyRWGM5LK4Nm32o76dWqn5r+v/r6gHFoBeylk8cCAOVsz/nvkb/74IWYzQB2tri8
ctAJTsj6FbQsMFE/b1kiER/YfqalaKEAl7FXx/hADPb/1nTasD7ExTilVFqSyX7m
ZckOc+EvqD7Q9t82KNQTXNaDDjA05vATjNGYiIhMxx0=
-----END EC PRIVATE KEY-----
EOT;

  /**
   * ED25519 key with password.
   *
   * There is an intentional new line.
   *
   * @var string
   */
  private $passwordEd25519 = <<<EOT
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAACmFlczI1Ni1jYmMAAAAGYmNyeXB0AAAAGAAAABD/PGNXox
+1UtmWjHr1Iv1MAAAAEAAAAAEAAAAzAAAAC3NzaC1lZDI1NTE5AAAAIBI9T/y0ifPKu5aP
SBuGglunwKW4jtlXLIb+h++EzJeHAAAAsGzVTyPHI5VzQGpsD4lOv4h2qqRyFFwlSjmSBq
XMkZsCc8FvmTw0CEIPyglugXzlK1IYmYP/ZwjsZdpRWEUdmxV12T6n+b3X+kMM9MBqOzPN
v6v5d7SQH1WfPht9HVm6iv/6Btyz+cesZUAX9SqVvSse03hYcln13NMvWBM0esGp/lkbE+
doHGg7MvhrrwHmISJHKcr8JPtmeQNPi+VRIugnCt5t2uplyzQ6vM53FwZ4
-----END OPENSSH PRIVATE KEY-----

EOT;

  /**
   * RSA1 key with password.
   *
   * @var string
   */
  private $passwordRsa1 = <<<EOT
U1NIIFBSSVZBVEUgS0VZIEZJTEUgRk9STUFUIDEuMQoAAwAAAAAAAAgACADKwfJDrAGKXG6nHs/NGIqzoKzzbaG6RcMmQxH33yiH3MnWp+sZl/wpAPoukdNnL6xSrDd6UjKfehmGZBjSE2t3ShJecoNL0/Y/IX5B/g5fOHs2Lq4CqDhoo21YAeEMB6Vzc2o4toE86WPOc/6Lmy2NeANyvGBn+slHlsnI8NU5rEKbDOGczww4YY2XhIrj45Yr+f6UqhERLRCH3sKKOfZ1YdOeGeJPR0lIsKVb62kQRVTkgWxa/+dkvPo98cuND/6Hg5jGZCOJVViZZgHjPYZXz0B3XGVTpjNbtcdCEx1vXWOXV16Lu4pSOq5uErZNz5siv7dth8hrTRrlW9ZMHwHhABEBAAEAAAAnbWFyYy5pbmdyYW1AbWFjYm9va3Byby1tYXJjaW5ncmFtLmxvY2FstDsuLvPcntJbCev3WRjy7Lc1m28VC/mFJqi+TBIuN6afzphdo3ZimsGx8cst2PN8B4yHmjgAYvW3fJJxx3B8/ThH1LVObkWr6vbyO3DkeukMqATz7Qx89wQLUypXmJYeK0CVB6keB/SBr0xhsHpWbE6FsV58M1Y9IaaXqYxT/VNvsEqt7+/FjJ9NN+8QhBJG08+hV5jxx0q4l0iAZHSB2Yu4Iwpr6wF/kVUg4s2oupIgjF3MUtF/TewykKRuow9jKAzJtOnMYRZo2yGEcoBLNbABeoEALrZPHsjp1yZrjcrsYx8sCuKXKVJ0jjHOWxgzGl4DJtaA1otMA3ogASPS5qxuuK0tvDZ3TsixMxZha0FJq63hI3JavQRDIADeRv/L5HE1QcQ9rw52MctnbI8JJS4PrNKftrRK0C/wWPJZvvzociAgLOfsx0WwM+3esf3mP2aQHaLBeobIOrtwvtL2Pr9ZaIu25lBqbcevuY+74v8Fi7Hjh93B9norCL6eME66PHygZzwW9DvsnDuKvOWPQqZbjanR0l+NHq4WI8ypxwvqmlSRHpcnc7tiW19p7et0PUhFhVWBfOCFOy6a/toJrw3D1lryqAcuJFsX+IVrnwnmo67TImC/n8tFgISbWVeiQdcVz88RiEFbnjByEvqdwzB+W7PMMAzLxJe5JFox1qDkuE5UzjOdMLrwfblx6ebx5m6Wy0kBFzxtciq/6lyCCNlO0r99fjlj+mMFa+onuiL0A0fp95092Q/HGxZlU68KhCZwZ5Ftykb4S1LWIBvP5ETdcVNrxKy1Dm7DqLfl9lRSgvZk9sRqlpSc51HNBgB2Bolt4vJ1A7SniNWs7KII5zXts6wLQuabft1r8mNgqCI=
EOT;

  /**
   * Random string.
   *
   * @var string
   */
  private $badData = <<<EOT
'Hello, World!'
EOT;

  /**
   * The original WipFactory configuration path.
   *
   * @var string
   */
  private static $originalConfigPath = NULL;

  /**
   * Sets up for testing.
   */
  public static function setUpBeforeClass() {
    self::$originalConfigPath = WipFactory::getConfigPath();
    WipFactory::setConfigPath(getcwd() . '/tests-wipng/unit/Acquia/Wip/Test/factory.cfg');
    WipFactory::reset();
  }

  /**
   * Resets after testing.
   */
  public static function tearDownAfterClass() {
    WipFactory::setConfigPath(self::$originalConfigPath);
    WipFactory::reset();
  }

  /**
   * Verifies the asymmetric private key is not stored in clear text.
   */
  public function testAsymmetricPrivateKeyIsSecure() {
    $unique_string = sha1(strval(mt_rand()));
    $wip = new BuildSteps();
    $wip->setAsymmetricPrivateKey($unique_string);
    $this->assertNotContains($unique_string, serialize($wip));
  }

  /**
   * Valids that the path contains a key.
   *
   * @param string $key_path
   *   The path to the key file.
   *
   * @return bool
   *   True is the file contains a valid key.
   */
  private function keycheck($key_path) {
    $command = sprintf(BuildSteps::KEY_CHECKER, $key_path);
    exec($command, $output, $exit_code);
    return $exit_code === 0;
  }

  /**
   * Verifies key detection works.
   */
  public function testIsSshKey() {
    $key_types = [
      'noPasswordKeyRsa',
      'noPasswordKeyDsa',
      'noPasswordKeyEcdsa',
      'noPasswordRsa1',
    ];
    foreach ($key_types as $name) {
      $filename = tempnam(sys_get_temp_dir(), 'st');
      if ($name == 'noPasswordRsa1') {
        $data = base64_decode($this->{$name});
      } else {
        $data = $this->{$name};
      }
      file_put_contents($filename, $data);
      chmod($filename, 0600);
      $this->assertTrue($this->keycheck($filename), sprintf('Key %s is valid', $name));
      @unlink($filename);
    }
  }

  /**
   * Verifies key detection works.
   */
  public function testHasPassword() {
    $key_types = [
      'passwordKeyRsa',
      'passwordKeyDsa',
      'passwordKeyEcdsa',
      'passwordEd25519',
      'passwordRsa1',
    ];
    foreach ($key_types as $name) {
      $filename = tempnam(sys_get_temp_dir(), 'st');
      if ($name == 'noPasswordRsa1') {
        $data = base64_decode($this->{$name});
      } else {
        $data = $this->{$name};
      }
      file_put_contents($filename, $data);
      chmod($filename, 0600);
      $this->assertFalse($this->keycheck($filename));
      @unlink($filename);
    }
  }

  /**
   * Verifies key rejection works.
   */
  public function testIsSshKeyWithBadKey() {
    $bad_key = str_replace(
      'END RSA PRIVATE KEY',
      'END RSA PRIVATE KE',
      $this->noPasswordKeyRsa
    );
    $filename = tempnam(sys_get_temp_dir(), 'st');
    file_put_contents($filename, $bad_key);
    $this->assertFalse($this->keycheck($filename));
    $filename = tempnam(sys_get_temp_dir(), 'st');
    file_put_contents($filename, $this->badData);
    $this->assertFalse($this->keycheck($filename));
  }

  /** @var WipContext*/
  private static $context;

  /** @var CloudCredentials*/
  private static $cloudCredentials;

  /**
   * Missing summary.
   */
  protected function setUp() {
    parent::setUp();
    self::$context = new WipContext();
    self::$cloudCredentials = AcquiaCloudTestSetup::getCreds();
  }

  /**
   * Missing summary.
   */
  private static function createBuildStep() {
    $build_steps = new BuildSteps();
    $options = new \stdClass();
    $options->site = self::$cloudCredentials->getSitegroup();
    $options->acquiaCloudCredentials = self::$cloudCredentials;
    $build_steps->setOptions($options);
    $build_steps->start(self::$context);

    return $build_steps;
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   */
  public function testGetSshKeyNameResultNotNull() {
    $build_steps = self::createBuildStep();
    $this->assertNotNull($build_steps->getSshKeyName());
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   */
  public function testGetSshKeyNameResultNotChanges() {
    $build_steps = self::createBuildStep();
    $expected = $build_steps->getSshKeyName();
    $actual = $build_steps->getSshKeyName();
    $this->assertSame($expected, $actual);
  }

  /**
   * Missing summary.
   *
   * @group BuildSteps
   */
  public function testGetSshKeyNameDiffersForDifferentObjects() {
    $build_steps_1 = self::createBuildStep();
    $build_steps_2 = self::createBuildStep();
    $this->assertNotEquals($build_steps_1->getSshKeyName(), $build_steps_2->getSshKeyName());
  }

}
