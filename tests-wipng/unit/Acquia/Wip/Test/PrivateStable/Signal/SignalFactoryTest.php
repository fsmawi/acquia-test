<?php

namespace Acquia\Wip\Test\PrivateStable\Signal;

use Acquia\Wip\AcquiaCloud\AcquiaCloudProcess;
use Acquia\Wip\AcquiaCloud\AcquiaCloudResult;
use Acquia\Wip\AcquiaCloud\DataTypes\AcquiaCloudTaskInfo;
use Acquia\Wip\Signal\Signal;
use Acquia\Wip\Signal\SignalFactory;
use Acquia\Wip\Signal\SignalType;

/**
 * Missing summary.
 */
class SignalFactoryTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testCreateWipCompletedSignal() {
    $signal = $this->getSignal('$acquia.wip.signal.wip.complete');
    $data = $signal->getData();
    $data->completedWipId = 11;
    $signal->setData($data);
    $domain_signal = SignalFactory::getDomainSpecificSignal($signal);
    $this->assertInstanceOf('Acquia\Wip\Signal\WipCompleteSignal', $domain_signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testCreateSshCompletedSignal() {
    $signal = $this->getSignal('$acquia.wip.signal.ssh.complete');
    $data = $signal->getData();
    $data->completedWipId = 11;
    $data->server = 'web-14';
    $data->startTime = time();
    $data->pid = 14241;
    $signal->setData($data);
    $domain_signal = SignalFactory::getDomainSpecificSignal($signal);
    $this->assertInstanceOf('Acquia\Wip\Signal\SshCompleteSignal', $domain_signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testCreateAcquiaCloudCompletedSignal() {
    $signal = $this->getSignal('$acquia.wip.signal.acquiacloud.complete');
    $data = $signal->getData();
    $data->id = 11;
    $data->queue = 'vcs_deploy';
    $data->state = AcquiaCloudTaskInfo::DONE;
    $data->description = 'Deploy a VCS path';
    $data->created = time() - 45;
    $data->started = time() - 44;
    $data->completed = time() - 1;
    $data->sender = 'scratchy';
    $signal->setData($data);
    $domain_signal = SignalFactory::getDomainSpecificSignal($signal);
    $this->assertInstanceof('Acquia\Wip\Signal\AcquiaCloudCompleteSignal', $domain_signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   */
  public function testCreateContainerCompletedSignal() {
    $signal = $this->getSignal('$acquia.wip.signal.container.complete');
    $domain_signal = SignalFactory::getDomainSpecificSignal($signal);
    $this->assertInstanceOf('Acquia\Wip\Signal\ContainerCompleteSignal', $domain_signal);
  }

  /**
   * Missing summary.
   *
   * @group Signal
   *
   * @expectedException \InvalidArgumentException
   */
  public function testCreateNoClassId() {
    $signal = $this->getSignal(NULL);
    $domain_signal = SignalFactory::getDomainSpecificSignal($signal);
  }

  /**
   * Missing summary.
   */
  private function getSignal($class_id) {
    $signal = new Signal();
    $signal->setType(SignalType::COMPLETE);
    $signal->setSentTime(time());
    $signal->setObjectId(11);
    $data = new \stdClass();
    $data->state = 'start';
    $data->classId = $class_id;
    $data->startTime = time();
    $signal->setData($data);
    return $signal;
  }

}
