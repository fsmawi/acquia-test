<?php

namespace Acquia\WipService\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Defines a constraint for validating monitor daemon pause mode values.
 */
class MonitorDaemonPauseMode extends Constraint {

  /**
   * The only parameter.
   *
   * @var string
   */
  public $name;

  /**
   * The violated constraint message.
   *
   * @var string
   */
  public $message = 'Invalid monitor daemon pause mode value for {{ name }} parameter, {{ value }} given.';

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return 'name';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return array('name');
  }

}
