<?php

namespace Acquia\WipService\Test;

use Acquia\WipService\Metrics\SegmentClient;

/**
 * Test the SegmentClient class.
 */
class SegmentClientTest extends \PHPUnit_Framework_TestCase {

  /**
   * Provide valid options for SegmentClient.
   *
   * @return array
   *   The options.
   */
  public function validOptionsProvider() {
    return [
      [
        [
          'sandbox' => FALSE,
          'project_key' => 'some_key',
          'environment' => 'some_environment',
        ],
      ],
      [
        [
          'sandbox' => TRUE,
          'project_key' => 'some_key',
          'environment' => 'some_environment',
        ],
      ],
    ];
  }

  /**
   * Provide options with invalid sandbox value for SegmentClient.
   *
   * @return array
   *   The options.
   */
  public function invalidSandboxOptionsProvider() {
    return [
      [
        [
          'sandbox' => 'false',
          'project_key' => 'some_key',
          'environment' => 'some_environment',
        ],
      ],
      [
        [
          'sandbox' => 1,
          'project_key' => 'some_key',
          'environment' => 'some_environment',
        ],
      ],
      [
        [
          'sandbox' => 1.1,
          'project_key' => 'some_key',
          'environment' => 'some_environment',
        ],
      ],
      [
        [
          'sandbox' => new \stdClass(),
          'project_key' => 'some_key',
          'environment' => 'some_environment',
        ],
      ],
      [
        [
          'sandbox' => [],
          'project_key' => 'some_key',
          'environment' => 'some_environment',
        ],
      ],
    ];
  }

  /**
   * Provide options with invalid project_key value for SegmentClient.
   *
   * @return array
   *   The options.
   */
  public function invalidProjectKeyOptionsProvider() {
    return [
      [
        [
          'sandbox' => FALSE,
          'project_key' => FALSE,
          'environment' => 'some_environment',
        ],
      ],
      [
        [
          'sandbox' => FALSE,
          'project_key' => 1,
          'environment' => 'some_environment',
        ],
      ],
      [
        [
          'sandbox' => FALSE,
          'project_key' => 1.1,
          'environment' => 'some_environment',
        ],
      ],
      [
        [
          'sandbox' => FALSE,
          'project_key' => new \stdClass(),
          'environment' => 'some_environment',
        ],
      ],
      [
        [
          'sandbox' => FALSE,
          'project_key' => [],
          'environment' => 'some_environment',
        ],
      ],
    ];
  }

  /**
   * Provide options with invalid environment value for SegmentClient.
   *
   * @return array
   *   The options.
   */
  public function invalidEnvironmentOptionsProvider() {
    return [
      [
        [
          'sandbox' => FALSE,
          'project_key' => 'some_key',
          'environment' => FALSE,
        ],
      ],
      [
        [
          'sandbox' => FALSE,
          'project_key' => 'some_key',
          'environment' => 1,
        ],
      ],
      [
        [
          'sandbox' => FALSE,
          'project_key' => 'some_key',
          'environment' => 1.1,
        ],
      ],
      [
        [
          'sandbox' => FALSE,
          'project_key' => 'some_key',
          'environment' => new \stdClass(),
        ],
      ],
      [
        [
          'sandbox' => FALSE,
          'project_key' => 'some_key',
          'environment' => [],
        ],
      ],
    ];
  }

  /**
   * Call protected/private method of a class.
   *
   * @param object &$object
   *   Instantiated object that we will run method on.
   * @param string $method_name
   *   Method name to call.
   * @param array $parameters
   *   Array of parameters to pass into method.
   *
   * @return mixed
   *   Method return.
   */
  public function invokeMethod(&$object, $method_name, array $parameters = []) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($method_name);
    $method->setAccessible(TRUE);
    return $method->invokeArgs($object, $parameters);
  }

  /**
   * Test SegmentClient construction with valid options.
   *
   * @param array $options
   *   SegmentClient options.
   *
   * @dataProvider validOptionsProvider
   */
  public function testValidOptions($options) {
    new SegmentClient($options);
  }

  /**
   * Test SegmentClient construction with invalid sandbox options.
   *
   * @param array $options
   *   SegmentClient options.
   *
   * @dataProvider invalidSandboxOptionsProvider
   *
   * @expectedException \InvalidArgumentException
   *
   * @expectedExceptionMessage The sandbox option must be set and must be a boolean.
   */
  public function testInvalidSandboxOptions($options) {
    new SegmentClient($options);
  }

  /**
   * Test SegmentClient construction with invalid project_key options.
   *
   * @param array $options
   *   SegmentClient options.
   *
   * @dataProvider invalidProjectKeyOptionsProvider
   *
   * @expectedException \InvalidArgumentException
   *
   * @expectedExceptionMessage The project_key option must be set and must be a string.
   */
  public function testInvalidProjectKeyOptions($options) {
    new SegmentClient($options);
  }

  /**
   * Test SegmentClient construction with invalid environment options.
   *
   * @param array $options
   *   SegmentClient options.
   *
   * @dataProvider invalidEnvironmentOptionsProvider
   *
   * @expectedException \InvalidArgumentException
   *
   * @expectedExceptionMessage The environment option must be set and must be a string.
   */
  public function testInvalidEnvironmentOptions($options) {
    new SegmentClient($options);
  }

  /**
   * Test that the SegmentClient adds the environment to the message.
   */
  public function testDecorateMessageAddsEnvironment() {
    $msg = [
      'userId' => '1',
      'event' => 'event',
    ];
    $client = new SegmentClient($this->validOptionsProvider()[0][0]);
    $decorated_message = $this->invokeMethod($client, 'decorateMessage', [$msg]);
    $this->assertEquals('some_environment', $decorated_message['context']['environment']);
  }

  /**
   * Test that the SegmentClient redacts acquia cloud credentials key.
   */
  public function testDecorateMessageRedactsAcquiaCloudCredentialsKey() {
    $msg = [
      'userId' => '1',
      'event' => 'event',
      'properties' => [
        'options' => ['acquiaCloudCredentials' => ['key' => 'some_key']],
      ],
    ];
    $client = new SegmentClient($this->validOptionsProvider()[0][0]);
    $decorated_message = $this->invokeMethod($client, 'decorateMessage', [$msg]);
    $this->assertEquals('*****', $decorated_message['properties']['options']['acquiaCloudCredentials']['key']);
  }

}
