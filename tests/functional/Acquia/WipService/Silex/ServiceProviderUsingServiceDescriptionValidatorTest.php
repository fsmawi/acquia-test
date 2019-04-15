<?php

namespace Acquia\WipService\Silex;

use Acquia\WipService\Silex\ConfigValidator\InvalidConfigurationException;
use Acquia\WipService\Silex\ConfigValidator\ServiceDescriptionConfigValidator;
use Silex\Application;

/**
 * Testing the service provider reading json config files and using the service description validator.
 */
class ServiceProviderUsingServiceDescriptionValidatorTest extends \PHPUnit_Framework_TestCase {

  /**
   * Creates dummy silex app.
   *
   * @return \Silex\Application
   *   The silex app.
   */
  private static function createApp() {
    $app = new Application();
    $app['root_dir'] = dirname(__DIR__);
    return $app;
  }

  /**
   * Testing with a valid service description in json format.
   */
  public function testValidateJsonValid() {
    $config = __DIR__ . '/../../../../resource/config/service-description-valid.json';
    $app = static::createApp();
    $app->register(new ValidatedConfigServiceProvider(
      $config,
      new ServiceDescriptionConfigValidator()
    ));
  }

  /**
   * Testing with a valid service description in json format.
   *
   * @expectedException \InvalidArgumentException
   *
   * @expectedExceptionMessageRegExp /The config file '.*' does not exist./
   */
  public function testValidateNonExistingFile() {
    $config = __DIR__ . '/../../../../resource/config/non-existing.json';
    $app = static::createApp();
    $app->register(new ValidatedConfigServiceProvider(
      $config,
      new ServiceDescriptionConfigValidator()
    ));
  }

  /**
   * Testing "operations" member existence validation.
   *
   * Testing that the validator fails with the correct message
   * to a service description file that has no "operations" member.
   *
   * @expectedException \Acquia\WipService\Silex\ConfigValidator\InvalidConfigurationException
   *
   * @expectedExceptionMessage The service description configuration requires a top-level "operations" member.
   */
  public function testMissingOperations() {
    $config = __DIR__ . '/../../../../resource/config/service-description-missing-operations.json';
    $app = static::createApp();
    $app->register(new ValidatedConfigServiceProvider(
      $config,
      new ServiceDescriptionConfigValidator()
    ));
  }

  /**
   * Testing "operations" member type validation.
   *
   * Testing that the validator fails with the correct message
   * to a service description file that where the "operations" member is not an array.
   *
   * @expectedException \Acquia\WipService\Silex\ConfigValidator\InvalidConfigurationException
   *
   * @expectedExceptionMessage The "operations" member of the service description configuration must be an array.
   */
  public function testOperationsNotArray() {
    $config = __DIR__ . '/../../../../resource/config/service-description-operations-not-array.json';
    $app = static::createApp();
    $app->register(new ValidatedConfigServiceProvider(
      $config,
      new ServiceDescriptionConfigValidator()
    ));
  }

  /**
   * Testing "allowedDuringMaintenance" parameter type validation.
   *
   * Testing that the validator fails with the correct message
   * to a service description file where the "allowedDuringMaintenance" parameter is not an array.
   */
  public function testAllowedDuringMaintenanceNotArray() {
    $config = __DIR__ . '/../../../../resource/config/service-description-allowed-at-mode-not-array.json';
    $app = static::createApp();
    try {
      $app->register(new ValidatedConfigServiceProvider(
        $config,
        new ServiceDescriptionConfigValidator()
      ));
    } catch (InvalidConfigurationException $e) {
      $this->assertSame(
        'The allowedDuringMaintenance parameter must be an array. (at operations.Ping.allowedDuringMaintenance)',
        (string) $e
      );
    } catch (\Exception $e) {
      $this->fail('Unexpected exception: ' . $e->getMessage());
    }
  }

  /**
   * Testing "allowedDuringMaintenance" parameter type validation.
   *
   * Testing that the validator fails with the correct message
   * to a service description file that contains only invalid server mode.
   */
  public function testOnlyInvalidStateMode() {
    $config = __DIR__ . '/../../../../resource/config/service-description-allowed-at-mode-only-invalid.json';
    $app = static::createApp();
    try {
      $app->register(new ValidatedConfigServiceProvider(
        $config,
        new ServiceDescriptionConfigValidator()
      ));
    } catch (InvalidConfigurationException $e) {
      $this->assertSame(
        'Invalid maintenance mode "INVALID_MODE". (at operations.Ping.allowedDuringMaintenance)',
        (string) $e
      );
    } catch (\Exception $e) {
      $this->fail('Unexpected exception: ' . $e->getMessage());
    }
  }

  /**
   * Testing "allowedDuringMaintenance" parameter type validation.
   *
   * Testing that the validator fails with the correct message
   * to a service description file that contains not only invalid server mode.
   */
  public function testNotOnlyInvalidStateMode() {
    $config = __DIR__ . '/../../../../resource/config/service-description-allowed-at-mode-not-only-invalid.json';
    $app = static::createApp();
    try {
      $app->register(new ValidatedConfigServiceProvider(
        $config,
        new ServiceDescriptionConfigValidator()
      ));
    } catch (InvalidConfigurationException $e) {
      $this->assertSame(
        'Invalid maintenance mode "INVALID_MODE". (at operations.Ping.allowedDuringMaintenance)',
        (string) $e
      );
    } catch (\Exception $e) {
      $this->fail('Unexpected exception: ' . $e->getMessage());
    }
  }

}
