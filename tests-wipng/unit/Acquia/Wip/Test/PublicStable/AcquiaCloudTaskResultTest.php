<?php

namespace Acquia\Wip\Test\PublicStable;

use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudTaskResult;

/**
 * Missing summary.
 */
class AcquiaCloudTaskResultTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testConstructor() {
    $result = new AcquiaCloudTaskResult();
    $this->assertNotEmpty($result->getSuccessExitCodes());
    try {
      $result->getPid();
      $this->fail("The process ID should not be set: %pid");
    } catch (\RuntimeException $e) {
      // This is correct behavior.
    }
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testConstructorWithDummyPid() {
    $result = new AcquiaCloudTaskResult(TRUE);
    $this->assertNotEmpty($result->getSuccessExitCodes());
    $this->assertNotEmpty($result->getPid());
  }

  /**
   * Missing summary.
   *
   * @group AcquiaCloud
   */
  public function testSetData() {
    $data = new AcquiaCloudTaskInfo();
    $result = new AcquiaCloudTaskResult();
    $result->setData($data);
    $this->assertEquals($data, $result->getData());
  }

}
