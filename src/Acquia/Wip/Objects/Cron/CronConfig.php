<?php

namespace Acquia\Wip\Objects\Cron;
 
// @todo - Need to have a way of doing a single site for a test run.
// Perhaps all sites set to false with a test site field?
/**
 * The CronConfig class describes a single cron configuration.
 */
class CronConfig {
 
  /**
   * The ID of this cron configuration instance.
   *
   * @var int
   */
  private $id = 0;

  /**
   * The human-readable name for this cron configuration instance.
   *
   * @var string
   */
  private $name = NULL;

  /**
   * The interval to run this cron configuration, expressed in cron format.
   *
   * @var string
   */
  private $interval = NULL;

  /**
   * The drush command to run.
   *
   * @var string
   */
  private $drushCommand = NULL;

  /**
   * The percent of all cron processes to use for this configuration.
   *
   * @var float
   */
  private $maxProcs = 100.0;

  /**
   * Creates a new instance of cron config, describing a particular configuration.
   *
   * @param int $id
   *   The configuration id.
   * @param string $name
   *   The name describing this cron job.
   * @param string $interval
   *   The cron interval description identifying when this cron job will run.
   * @param string $drush_command
   *   The drush command that will be called on all of the specified sites.
   * @param float $procs
   *   The percent of available processes to use.
   */
  public function __construct($id, $name, $interval, $drush_command, $procs) {
    $this->id = $id;
    $this->name = $name;
    $this->interval = $interval;
    $this->drushCommand = $drush_command;
    $this->maxProcs = $procs;
  }

  /**
   * Returns the ID of this CronConfig instance.
   *
   * @return int
   *   The configuration ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Returns the name of this CronConfig instance.
   *
   * @return string
   *   The name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns the interval which this cron configuration should be invoked.
   *
   * @return string
   *   The cron interval.
   */
  public function getInterval() {
    return $this->interval;
  }

  /**
   * Returns the drush command used to invoke cron.
   *
   * @return string
   *   The drush command.
   */
  public function getDrushCommand() {
    return $this->drushCommand;
  }

  /**
   * Returns the maximum percentage of total processes to use for this config.
   *
   * @return float
   *   The maximum percent of procs.
   */
  public function getMaxProcs() {
    return $this->maxProcs;
  }

}
