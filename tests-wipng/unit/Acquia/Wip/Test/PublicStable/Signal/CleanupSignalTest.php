<?php

namespace Acquia\Wip\Test\PublicStable\Signal;

use Acquia\Wip\Signal\CleanupSignal;
use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalFactory;

/**
 * Missing summary.
 */
class CleanupSignalTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testInstantiation() {
    $signal = new CleanupSignal();
    $this->assertNotEmpty($signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSetAction() {
    $signal = new CleanupSignal();
    $signal->setAction(CleanupSignal::ACTION_REQUEST);
    $this->assertEquals(CleanupSignal::ACTION_REQUEST, $signal->getAction());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetActionInvalidType() {
    $value = '1';
    $signal = new CleanupSignal();
    $signal->setAction($value);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetActionInvalidValue() {
    $value = 4;
    $signal = new CleanupSignal();
    $signal->setAction($value);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testInitializeFromSignalData() {
    $action = CleanupSignal::ACTION_REQUEST;
    $payload = new \stdClass();
    $payload->action = $action;
    $signal_data = new \stdClass();
    $signal_data->payload = $payload;

    $data_signal = new CleanupSignal();
    $data_signal->initializeFromSignalData($signal_data);
    $this->assertEquals($action, $data_signal->getAction());
    $this->assertEquals($payload, $data_signal->getPayload());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSetResourceType() {
    $type = 'wakaflocka';
    $signal = new CleanupSignal();
    $signal->setResourceType($type);
    $this->assertEquals($type, $signal->getResourceType());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetResourceTypeNonString() {
    $type = new \stdClass();
    $signal = new CleanupSignal();
    $signal->setResourceType($type);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetResourceTypeEmpty() {
    $type = '';
    $signal = new CleanupSignal();
    $signal->setResourceType($type);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSetResourceId() {
    $id = 'wakaflocka';
    $signal = new CleanupSignal();
    $signal->setResourceId($id);
    $this->assertEquals($id, $signal->getResourceId());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetResourceIdNonString() {
    $id = 15;
    $signal = new CleanupSignal();
    $signal->setResourceId($id);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetResourceIdEmpty() {
    $id = '';
    $signal = new CleanupSignal();
    $signal->setResourceId($id);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSetResourceName() {
    $name = 'name';
    $signal = new CleanupSignal();
    $signal->setResourceName($name);
    $this->assertEquals($name, $signal->getResourceName());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetResourceNameNonString() {
    $name = 15;
    $signal = new CleanupSignal();
    $signal->setResourceName($name);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetResourceNameEmpty() {
    $name = '';
    $signal = new CleanupSignal();
    $signal->setResourceName($name);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testConvertFieldsToObject() {
    $action = CleanupSignal::ACTION_REQUEST;
    $type = 'sshkey';
    $id = '1234';
    $signal = new CleanupSignal();
    $signal->setAction($action);
    $signal->setResourceType($type);
    $signal->setResourceId($id);
    $object = $signal->convertFieldsToObject();

    $new_signal = new CleanupSignal();
    $new_signal->initializeFromSignalData($object);
    $this->assertEquals($action, $new_signal->getAction());
    $this->assertEquals($type, $new_signal->getResourceType());
    $this->assertEquals($id, $new_signal->getResourceId());
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testSignalFactory() {
    $action = CleanupSignal::ACTION_REQUEST;
    $type = 'sshkey';
    $id = '1234';
    $signal = new CleanupSignal();
    $signal->setAction($action);
    $signal->setResourceType($type);
    $signal->setResourceId($id);
    $object = $signal->convertFieldsToObject();
    $new_signal = new Signal();
    $new_signal->setData($object);
    $final_signal = SignalFactory::getDomainSpecificSignal($new_signal);
    $this->assertInstanceOf('Acquia\Wip\Signal\CleanupSignal', $final_signal);

    /** @var CleanupSignal $final_signal */
    $this->assertEquals($action, $final_signal->getAction());
    $this->assertEquals($type, $final_signal->getResourceType());
    $this->assertEquals($id, $final_signal->getResourceId());
  }

}
