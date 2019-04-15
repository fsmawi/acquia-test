<?php

namespace Acquia\Wip\Test\PrivateUnstable\Objects;

use Acquia\Wip\Modules\NativeModule\BuildSteps;
use Acquia\Wip\WipFactory;

/**
 * Unit tests for the AbstractPipelineWip.
 */
class AbstractPipelineWipTest extends \PHPUnit_Framework_TestCase {

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
   * Verifies the Pipeline API key is not stored in clear text.
   */
  public function testPipelineApiKeyIsSecure() {
    $unique_string = sha1(strval(mt_rand()));
    $options = new \stdClass();
    $options->pipelineApiKey = $unique_string;
    $wip = new BuildSteps();
    $wip->setOptions($options);
    $this->assertNotContains($unique_string, serialize($wip));
  }

  /**
   * Verifies the Pipeline API secret is not stored in clear text.
   */
  public function testPipelineApiSecretIsSecure() {
    $unique_string = sha1(strval(mt_rand()));
    $options = new \stdClass();
    $options->pipelineApiSecret = $unique_string;
    $wip = new BuildSteps();
    $wip->setOptions($options);
    $this->assertNotContains($unique_string, serialize($wip));
  }

}
