<?php

namespace Acquia\Wip\Test\PrivateUnstable\Objects\Modules;

use Acquia\Wip\Objects\Modules\WipModuleConfigReader;
use Acquia\Wip\TaskPriority;
use Acquia\Wip\WipLogLevel;
use Acquia\Wip\WipModule;
use Acquia\Wip\WipModuleInterface;
use Acquia\Wip\WipModuleTaskInterface;

/**
 * Tests the Wip Module configuration reader.
 */
class WipModuleConfigReaderTest extends \PHPUnit_Framework_TestCase {

  /**
   * The path to the test module configuration file.
   */
  const TEST_MODULE_INI_PATH = 'tests-wipng/unit/Acquia/Wip/Test/PrivateUnstable/Objects/Modules/TestModule.ini';

  /**
   * The name of the test module.
   */
  const TEST_MODULE_NAME = 'TestModule';

  /**
   * Tests the constructor.
   *
   * @param WipModuleInterface $module
   *   The module.
   * @param string $module_data
   *   The module data.
   * @param bool $successful
   *   Whether the call should be successful.
   * @param string $exception
   *   The exception that should be thrown.
   * @param string $error_message
   *   The error message that is expected.
   *
   * @group Module
   *
   * @dataProvider constructorProvider
   */
  public function testPopulate(WipModuleInterface $module, $module_data, $successful, $exception, $error_message) {
    if (!$successful) {
      $this->setExpectedException($exception, $error_message);
    }

    WipModuleConfigReader::populateModule($module, $module_data);
  }

  /**
   * Provides an array of values for testing the constructor.
   *
   * @return array
   *   The array of values to test.
   */
  public function constructorProvider() {
    $module = new WipModule(self::TEST_MODULE_NAME);
    $config_data = file_get_contents(self::TEST_MODULE_INI_PATH);

    return array(
      array(
        $module,
        $config_data,
        TRUE,
        NULL,
        NULL,
      ),
      array(
        $module,
        NULL,
        FALSE,
        'DomainException',
        'Unable to read the module configuration file "/mnt/tmp/test.prod/modules/TestModule/module.ini".',
      ),
    );
  }

  /**
   * Tests parsing the ini file.
   */
  public function testParsing() {
    $reader = new WipModuleConfigReader();

    $module = new WipModule('TestModule');
    $config_data = file_get_contents('tests-wipng/unit/Acquia/Wip/Test/PrivateUnstable/Objects/Modules/TestModule.ini');
    $reader->parse($module->getName(), $config_data);
    $this->assertEquals($reader->getEnabled(), FALSE);
    $this->assertEquals($reader->getIncludes(), ['autoload.php']);

    $tasks = $reader->getTasks();
    $this->assertNotEmpty($tasks);
    $this->assertCount(1, $tasks);
    /** @var WipModuleTaskInterface $task */
    $task = array_shift($tasks);
    $this->assertEquals('TestGroup', $task->getGroupName());
    $this->assertEquals('Acquia\Wip\Modules\TestTask', $task->getClassName());
    $this->assertEquals('Alert', WipLogLevel::toString($task->getLogLevel()));
    $this->assertEquals('Medium', TaskPriority::toString($task->getPriority()));
  }

}
