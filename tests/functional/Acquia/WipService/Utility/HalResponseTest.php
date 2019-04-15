<?php

namespace Acquia\WipService\Utility;

use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Teapot\StatusCode;

/**
 * Missing summary.
 */
class HalResponseTest extends AbstractFunctionalTest {

  /**
   * Creates a response object for testing.
   *
   * @return HalResponse
   *   The response.
   */
  protected function getResponse() {
    $data = $this->getDefaultData();
    $hal = $this->app['hal']('tasks/1', $data);
    return HalResponse::create($hal, StatusCode::OK);
  }

  /**
   * Retrieves a default data set for testing.
   *
   * @return array
   *   The data.
   */
  public function getDefaultData() {
    return array(
      'task_id' => 1,
    );
  }

  /**
   * Tests the constructor.
   */
  public function testConstruct() {
    $data = $this->getDefaultData();
    $hal = $this->app['hal']('tasks/1', $data);
    $response = new HalResponse($hal, StatusCode::OK);

    $this->assertTrue($response->isOk());
  }

  /**
   * Tests that the create factory method returns a HalResponse.
   */
  public function testCreateReturnsHalResponse() {
    $response = $this->getResponse();
    $this->assertSame('Acquia\WipService\Http\HalResponse', get_class($response));
  }

  /**
   * Tests the create factory method.
   */
  public function testCreate() {
    $response = $this->getResponse();
    $this->assertTrue($response->isOk());
  }

  /**
   * Tests that passing an invalid content argument throws an exception.
   *
   * @param mixed $content
   *   The invalid content to trigger the exception.
   *
   * @dataProvider invalidContentParameterProvider
   *
   * @expectedException \InvalidArgumentException
   *
   * @expectedExceptionMessage The content argument must be of type \Nocarrier\Hal
   */
  public function testCreateInvalidType($content) {
    HalResponse::create($content);
  }

  /**
   * Provides invalid content parameters for the HalResponse::create() method.
   *
   * @return array
   *   A multidimensional array of parameters.
   */
  public function invalidContentParameterProvider() {
    return array(
      array(''),
      array('foo'),
      array(NULL),
      array(TRUE),
      array(0),
      array(0.1),
      array(new \stdClass()),
      array(array()),
    );
  }

  /**
   * Tests the getData method.
   */
  public function testGetData() {
    $response = $this->getResponse();
    $default_data = $this->getDefaultData();
    $hal_data = $response->getData();
    $response_data = $hal_data->getData();

    $this->assertEquals($default_data, $response_data);
  }

  /**
   * Tests the setData method.
   */
  public function testSetData() {
    $response = $this->getResponse();

    $uri = 'tasks/2';
    $data = array(
      'task_id' => 2,
    );
    $hal = $this->app['hal']($uri, $data);

    $response->setData($hal);
    $new_data = json_decode($response->getContent());

    $this->assertEquals($data['task_id'], $new_data->task_id);
    $this->assertEquals($uri, $new_data->_links->self->href);
  }

  /**
   * Tests that the default format is JSON.
   */
  public function testDefaultFormat() {
    $response = $this->getResponse();
    json_decode($response->getContent());
    $this->assertTrue(json_last_error() == JSON_ERROR_NONE);
  }

  /**
   * Tests the setFormat method.
   */
  public function testSetFormat() {
    $response = $this->getResponse();
    $response->setFormat($response::AS_XML);
    $content = $response->getContent();

    libxml_use_internal_errors(TRUE);
    $xml = simplexml_load_string($content);

    $this->assertNotFalse($xml);
  }

  /**
   * Tests the update method.
   */
  public function testUpdate() {
    $response = $this->getResponse();

    $uri = 'tasks/2';
    $data = array(
      'task_id' => 2,
    );
    $hal = $this->app['hal']($uri, $data);

    $response->setData($hal);

    $reflection = new \ReflectionClass('\Acquia\WipService\Http\HalResponse');
    $method = $reflection->getMethod('update');
    $method->setAccessible(TRUE);

    $content = $method->invokeArgs($response, array());
    $new_data = json_decode($response->getContent());

    $this->assertEquals($data['task_id'], $new_data->task_id);
    $this->assertEquals($uri, $new_data->_links->self->href);
  }

  /**
   * Tests the exception in the update method.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testUpdateException() {
    $response = $this->getResponse();
    $response->setFormat(-999);
  }

}
