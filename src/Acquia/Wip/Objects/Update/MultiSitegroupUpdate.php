<?php

namespace Acquia\Wip\Objects\Update;

use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipLogLevel;

/**
 * The MultiSitegroupUpdate performs an update on multiple sitegroups.
 */
class MultiSitegroupUpdate extends BasicWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The raw JSON data that describes the update.
   *
   * @var string
   */
  private $rawData = NULL;

  /**
   * The parameter document that describes the update.
   *
   * @var IndependentDocument
   */
  private $parameterDoc = NULL;

  /**
   * Instantiates a new instance of MultiSitegroupUpdate with the specified doc.
   *
   * @param string $parameter_doc
   *   The document containing the parameters for the update.
   */
  public function __construct($parameter_doc = NULL) {
    parent::__construct();
    $this->parameterDoc = NULL;
  }

  /**
   * The state table that will be executed by this Wip object.
   *
   * @var string
   */
  protected $stateTable = <<<EOT
start {
  * parseRelationshipDocument
}

parseRelationshipDocument:checkParseStatus {
  success      callSitegroupUpdaters
  fail         failure
}

# Creates a sitegroup updater for each sitegroup and waits for their completion.
callSitegroupUpdaters:checkDocrootUpdaters {
  create       callSitegroupUpdaters       wait=1
  wait         callSitegroupUpdaters       wait=10 exec=false
  success      finish
  fail         failure
}

failure {
  *            finish
  !            finish
}

EOT;

  /**
   * Parses the parameter document that describes the update.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @TODO: THis should be changed to a transition method on start that simply
   * verifies the document has been set.
   */
  public function parseRelationshipDocument(WipContextInterface $wip_context) {
    if (!empty($this->parameterDoc)) {
      // The document has already been parsed.
      $this->rawData = NULL;
    }
    printf("Document: %s", print_r($this->parameterDoc, TRUE));
    /*
    else {
    try {
    $this->parameterDoc = new ParameterDocument($this->rawData, array('siteGroups' => 'Acquia\Wip\Objects\SiteGroup'));
    // Clear the raw data to save memory.
    $this->rawData = NULL;
    } catch (\Exception $e) {
    $this->log(WipLogLevel::FATAL, sprintf('Unable to parse the parameter document. Error: %s', $e->getMessage()));
    }
    }
     */
  }

  /**
   * Invoke and monitor sitegroup updaters.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function callSitegroupUpdaters(WipContextInterface $wip_context) {
    $start = time();
    $max_run_time = 60;
    if (NULL === $wip_context->remainingSitegroups) {
      // TODO: Be careful - the wip_context will be cleared.
      $wip_context->remainingSitegroups = array_keys($this->parameterDoc->siteGroups);
    }
    $wip_api = $this->getWipApi();
    $processed_sitegroups = array();
    foreach ($wip_context->remainingSitegroups as $sitegroup_name) {
      $this->log(WipLogLevel::DEBUG, sprintf('Starting sitegroup updater for "%s"', $sitegroup_name));

      // Start a Wip object that will update the sitegroup.
      $child_updater = new BasicWip(); // TODO: We need a real class here.
      $wip_api->addChild($child_updater, $wip_context, $this);
      $processed_sitegroups[] = $sitegroup_name;
      if (time() >= $start + $max_run_time) {
        break;
      }
    }
    $wip_context->remainingSitegroups = array_diff($wip_context->remainingSitegroups, $processed_sitegroups);
  }

  /**
   * The default failure state in the FSM.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   * @param \Exception $exception
   *   The exception that caused the failure (assuming the failure was caused
   *   by an exception.
   *
   * @throws \Exception
   *   The passed in exception.
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL) {
  }

  /**
   * Ensures the parameter document has been parsed.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
   *   'success' - The document has been parsed and verified.
   *   'fail'    - The document has not been properly parsed.
   */
  public function checkParseStatus(WipContextInterface $wip_context) {
    $result = 'fail';
    if (!empty($this->parameterDoc) && empty($this->rawData)) {
      // TODO: Verify we have all the required components.
      if (!empty($this->parameterDoc->siteGroups)) {
        $result = 'success';
      }
    }
    return $result;
  }

  /**
   * Indicates the status of the docroot updaters.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
   *   'create' - Child updater instances are still being created.
   *   'success' - All tasks have completed successfully.
   *   'wait' - One or more tasks are still running.
   *   'fail' - At least one task failed.
   */
  public function checkDocrootUpdaters(WipContextInterface $wip_context) {
    if (NULL === $wip_context->remainingSitegroups || !empty($wip_context->remainingSitegroups)) {
      $result = 'create';
    } else {
      $result = $this->checkWipTaskStatus($wip_context);
      if ($result == 'uninitialized') {
        $result = 'create';
      }
    }
    return $result;
  }

}
