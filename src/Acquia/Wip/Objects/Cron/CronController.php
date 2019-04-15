<?php

namespace Acquia\Wip\Objects\Cron;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\Implementation\WipTaskApi;
use Acquia\Wip\Objects\ParameterDocument;
use Acquia\Wip\Objects\Setup\VerifySshKeys;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipLogLevel;
use Cron\CronExpression;

/**
 * The CronController is responsible for managing MultisiteCron instances.
 *
 * This is a long-lived instance, meant to be invoked once per day.
 */
class CronController extends BasicWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The result of the task.
   *
   * @var object
   *
   * @todo Change this to a class.
   */
  private $result = NULL;

  /**
   * The parameter document.
   *
   * @var ParameterDocument
   */
  private $parameterDocument;

  /**
   * Indicates whether the children have been created.
   *
   * @var bool
   */
  private $childrenCreated = FALSE;

  /**
   * The cron configuration.
   *
   * @var CronConfig
   */
  private $cronConfig;

  /**
   * The Unix timestamp indicating when the next run should begin.
   *
   * @var int
   */
  private $nextRun = 0;

  /**
   * The state table that will be executed by this Wip object.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
start {
  *                  ensureSshKeys
}

ensureSshKeys:checkWipTaskStatus {
  *                  setCronTime
  wait               ensureSshKeys         wait=300 exec=false
  fail               failure
}

setCronTime:waitForCronTime {
  success            okToRun
  wait               setCronTime           wait=30 exec=false
}

okToRun:checkAbort {
  success            invokeMultisiteCron
  abort              finish
}

invokeMultisiteCron:checkWipTaskStatus {
  success            report
  wait               invokeMultisiteCron   wait=300 exec=false
  uninitialized      report
  fail               report
}

report {
  *                  runComplete
}

# If we haven't been aborted, run again.
runComplete:checkAbort {
  # @todo Uncomment the success transition before going into production. This is
  #   done to make testing easy. For now, only run once.
  #success           setCronTime
  *                  finish
}

failure {
  *                  finish
}

EOT;

  /**
   * Sets the cron configuration this instance will run.
   *
   * @param CronConfig $cron_config
   *   The cron configuration.
   */
  public function setCronConfig(CronConfig $cron_config) {
    $this->cronConfig = $cron_config;
  }

  /**
   * Gets the cron configuration associated with this instance.
   *
   * @return CronConfig
   *   The cron configuration.
   */
  public function getCronConfig() {
    return $this->cronConfig;
  }

  /**
   * Sets the parameter document that describes which sites to run cron on.
   *
   * @param ParameterDocument $document
   *   The parameter document.
   */
  public function setParameterDocument(ParameterDocument $document) {
    $this->parameterDocument = $document;
  }

  /**
   * Gets the parameter document that describes where cron will run.
   *
   * @return ParameterDocument
   *   The document.
   */
  public function getParameterDocument() {
    return $this->parameterDocument;
  }

  /**
   * Initializes the CronController for a new run.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function start(WipContextInterface $wip_context) {
    parent::start($wip_context);
    $this->initializeResult();
    $this->childrenCreated = FALSE;
    $this->nextRun = 0;
  }

  /**
   * Initializes the result, which will be used to generate a sensible summary.
   */
  private function initializeResult() {
    $result = new \stdClass();
    $result->totalSites = 0;
    $result->totalTime = 0;
    $result->totalFailures = 0;
    $result->failedSites = array();
    $result->errors = array();
    $this->result = $result;
  }

  /**
   * Ensures the Ssh keys have been established.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function ensureSshKeys(WipContextInterface $wip_context) {
    $verify = new VerifySshKeys();
    $verify->setParameterDocument($this->getParameterDocument());
    $verify->setLogLevel($this->getLogLevel());

    $wip_api = new WipTaskApi();
    $wip_api->addChild($verify, $wip_context, $this);
  }

  /**
   * Sets the time that this instance should run.
   */
  public function setCronTime() {
    $runtime = $this->getNextRuntime();
    $this->nextRun = $runtime;
    // TODO - Need a way to set the wait time.  Something like...
    // This requires an additional call either in the BasicWip class or in the
    // iterator.
  }

  /**
   * Transition that prevents this instance from running until it is time.
   *
   * @return string
   *   'success' - Ok to run cron.
   *   'wait'    - The scheduled time has not arrived yet.
   */
  public function waitForCronTime() {
    $result = 'wait';
    if (time() >= $this->nextRun) {
      $result = 'success';
    }
    return $result;
  }

  /**
   * Empty state method that supports a transition that honors abort.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function okToRun(WipContextInterface $wip_context) {
  }

  /**
   * Invokes child objects to call cron.
   *
   * The first time through, the child Wip objects are created. Subsequent
   * calls result in the children that have completed being restarted.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function invokeMultisiteCron(WipContextInterface $wip_context) {
    // TODO: Break this into 2 helper methods.
    $error_message = '';
    $success_message = '';

    $wip_api = $this->getWipApi();
    if (!$this->childrenCreated) {
      $sitegroups = $this->parameterDocument->siteGroups;
      foreach ($sitegroups as $sitegroup) {
        // @var SiteGroup $sitegroup.
        $environment_document = $this->parameterDocument->extract(array(
          'siteGroup' => $sitegroup->getName(),
          'environment' => $sitegroup->getLiveEnvironment(),
        ));

        $child = new MultisiteCron();
        $child->setEnvironment($environment_document);
        $child->setCronConfig($this->getCronConfig());
        $child->setLogLevel($this->getLogLevel());

        $this->log(WipLogLevel::DEBUG, sprintf('Starting MultisiteCron for docroot "%s"', $sitegroup->getName()));
        $wip_api->addChild($child, $wip_context, $this);
      }
      $this->childrenCreated = TRUE;
    } else {
      // Be sure that all completed processes are converted to results.
      $wip_api->getWipTaskStatus($wip_context, $this->getWipLog());

      // The processes represent Wip objects that have not completed.
      $processes = $wip_api->getWipTaskProcesses($wip_context);
      foreach ($processes as $process) {
        $error_message .= sprintf('The Wip process %d is still running;  ', $process->getId());
      }

      // The WipResults indicate which Wip objects have completed; only restart
      // those.
      $completed_tasks = $wip_api->getWipTaskResults($wip_context);
      foreach ($completed_tasks as $wip_result) {
        try {
          $wip_process = $wip_api->restartTask($wip_result->getPid(), $wip_context, $this->getWipLog());
          $success_message .= sprintf('Restarted task %d; ', $wip_process->getId());
        } catch (\Exception $e) {
          $error_message .= sprintf('Failed to restart task %d: %s; ', $wip_result->getPid(), $e->getMessage());
        }
      }
    }
    if (!empty($error_message)) {
      $this->log(WipLogLevel::ERROR, $error_message);
    }
    if (!empty($success_message)) {
      $this->log(WipLogLevel::INFO, $error_message);
    }
  }

  /**
   * Reports the results of the cron invocation.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function report(WipContextInterface $wip_context) {
    // TODO: We need signals in order to implement this.
  }

  /**
   * Called when the cron run is complete.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function runComplete(WipContextInterface $wip_context) {
  }

  /**
   * Checks to see if this instance should run again.
   *
   * TODO: This used to work by getting the cron configuration from the site
   * factory. That doesn't work anymore so perhaps there is a signal solution
   * that can be used to tell this instance to abort (continue running cron
   * until it completes, but don't run it again). That is different from
   * terminate, which should stop the process immediately.
   *
   * @return string
   *   'success' - This instance has not been aborted.
   *   'abort'   - This instance has been aborted.
   */
  public function checkAbort() {
    $result = 'success';
    return $result;
  }

  /**
   * Gets the Unix timestamp indicating when cron should run again.
   *
   * @return int
   *   The Unix timestamp representing the time cron should be invoked.
   */
  private function getNextRuntime() {
    $cron = CronExpression::factory($this->getCronConfig()->getInterval());
    return $cron->getNextRunDate()->getTimestamp();
  }

}
