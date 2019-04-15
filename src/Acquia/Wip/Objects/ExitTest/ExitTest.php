<?php

namespace Acquia\Wip\Objects\ExitTest;

use Acquia\Wip\ExitMessage;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\WipContextInterface;

/**
 * Simple Wip object that exits with a specified exit status and message.
 */
class ExitTest extends BasicWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The exit message.
   *
   * @var string
   */
  private $exitMessage = 'Use the "exitMessage" option to set the exit message.';

  /**
   * The exit code.
   *
   * @var int
   */
  private $exitCode = 0;

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    if (!empty($options->exitMessage)) {
      $this->exitMessage = strval($options->exitMessage);
    }
    if (!empty($options->exitCode)) {
      $this->exitCode = intval($options->exitCode);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finish(WipContextInterface $context) {
    $exit_message = new ExitMessage($this->exitMessage);
    $this->setExitMessage($exit_message);
    $context->setExitCode($this->exitCode);
  }

}
