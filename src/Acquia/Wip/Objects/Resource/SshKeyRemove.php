<?php

namespace Acquia\Wip\Objects\Resource;

use Acquia\Wip\AcquiaCloud\AcquiaCloud;
use Acquia\Wip\EnvironmentInterface;
use Acquia\Wip\ExitMessage;
use Acquia\Wip\Implementation\BasicWip;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipLogLevel;

/**
 * Removes an SSH key resource from the Acquia Cloud API.
 */
class SshKeyRemove extends BasicWip {

  /**
   * The version associated with this Wip class.
   */
  const CLASS_VERSION = 1;

  /**
   * The environment used to interact with Cloud API.
   *
   * @var EnvironmentInterface
   */
  private $environment;

  /**
   * The ID of the SSH key to remove.
   *
   * @var int
   */
  private $keyId = NULL;

  /**
   * The nickname of the SSH key registered with Hosting.
   *
   * @var string
   */
  private $keyName = NULL;

  /**
   * The state table that will be executed by this Wip object.
   *
   * @var string
   */
  protected $stateTable = <<<EOT

start {
  * verifyConfiguration
}

verifyConfiguration:checkConfiguration {
  success verifyKey
  fail    failure
}

verifyKey:checkIfKeyExists [acquiCloudApi] {
  success removeKey
  fail    finish
  retry   verifyKey wait=30 max=3
}

# Make sure that no failures after this point go back to the failure state
# because that would set up an infinite loop.
failure {
  * finish
  ! finish
}

removeKey:checkResultStatus [acquiCloudApi] {
  success finish
  wait removeKey wait=10 exec=false
  fail removeKey wait=10 exec=true max=3
  * alertKeyNotRemoved
  ! alertKeyNotRemoved
}

alertKeyNotRemoved {
  * failure
  ! finish
}

EOT;

  /**
   * {@inheritdoc}
   */
  public function start(WipContextInterface $wip_context) {
    parent::start($wip_context);
    $parameter_document = $this->getParameterDocument();
    $this->environment = $this->extractEnvironment($parameter_document);
  }

  /**
   * Verifies the configuration is complete.
   */
  public function verifyConfiguration() {
    // The transition method does all of the work.
  }

  /**
   * Retrieves the keys from the Cloud.
   */
  public function verifyKey() {
    // The transition method does all of the work.
  }

  /**
   * {@inheritdoc}
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL) {
    $this->setExitCode(IteratorStatus::ERROR_SYSTEM);
    $key_id = $this->getKeyId();
    $key_name = $this->getKeyName();
    if (!empty($key_name) && !empty($key_id)) {
      $message = sprintf('Failed to remove SSH key "%s" [id: %d].', $key_name, $key_id);
    } else {
      if (!empty($key_name)) {
        $message = sprintf('Failed to remove SSH key "%s".', $key_name);
      } elseif (!empty($key_id)) {
        $message = sprintf('Failed to remove SSH key with ID %d.', $key_id);
      } else {
        $message = sprintf('Failed to remove SSH key - no key name or ID were provided.');
      }
    }
    $exit_message = new ExitMessage($message, WipLogLevel::ERROR);
    $this->setExitMessage($exit_message);
  }

  /**
   * Remove the SSH key from the Cloud.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function removeKey(WipContextInterface $wip_context) {
    $cloud_api = $this->getAcquiaCloudApi();
    $wip_log = $this->getWipLog();
    $cloud_api->clearAcquiaCloudProcesses($wip_context, $wip_log);
    $cloud_api->clearAcquiaCloudResults($wip_context, $wip_log);
    try {
      $cloud = new AcquiaCloud($this->environment, $wip_log);
      $process = $cloud->deleteSshKey($this->getKeyId());
      $cloud_api->setAcquiaCloudProcess($process, $wip_context, $wip_log);
    } catch (\Exception $e) {
      $this->log(
        WipLogLevel::ERROR,
        sprintf('Failed to delete the SSH key: %d (%s)', $this->getKeyId(), $this->getKeyName())
      );
    }
  }

  /**
   * Called after unsuccessful key remove calls.
   */
  public function alertKeyNotRemoved() {
    $this->notifyFailure();
  }

  /**
   * Checks that this object has been configured successfully.
   *
   * @return string
   *   'success' - This object was configured successfully.
   *   'fail' - The object configuration failed.
   */
  public function checkConfiguration() {
    $result = 'success';
    if (NULL === $this->getKeyName() && NULL === $this->getKeyId()) {
      $result = 'fail';
    }
    return $result;
  }

  /**
   * Checks that the key is present in the Cloud response.
   *
   * @return string
   *   'success' - The key is present.
   *   'fail' - No such key in the Cloud.
   *   'retry' - The Cloud API call failed and the check should be retried.
   */
  public function checkIfKeyExists() {
    $result = 'fail';
    $key_id = $this->getKeyId();
    $key_name = $this->getKeyName();

    if (NULL !== $key_id) {
      try {
        $key_name = $this->getKeyNameFromKeyId($key_id);
        if (NULL !== $key_name) {
          $this->setKeyName($key_name);
          $result = 'success';
        }
      } catch (\Exception $e) {
        // The Cloud API call failed.
        $result = 'retry';
      }
    } elseif (NULL !== $key_name) {
      try {
        $key_id = $this->getKeyIdFromKeyName($key_name);
        if (NULL !== $key_id) {
          $this->setKeyId($key_id);
          $result = 'success';
        }
      } catch (\Exception $e) {
        // The Cloud API call failed.
        $result = 'retry';
      }
    } else {
      // There is not enough information to look up the key.
      // This should never happen because we do validation and exit with a
      // failure before trying to validate the key.
    }
    return $result;
  }

  /**
   * Gets the Hosting SSH key ID from the key nickname.
   *
   * @param string $key_name
   *   The SSH key nickname.
   *
   * @return int|null
   *   The Hosting key ID or NULL if the key is not registered in Hosting.
   *
   * @throws \DomainException
   *   If the Cloud API call used to look up the key failed.
   */
  public function getKeyIdFromKeyName($key_name) {
    $result = NULL;
    $cloud = new AcquiaCloud($this->environment, $this->getWipLog());
    $key_result = $cloud->listSshKeys();
    if ($key_result->isSuccess()) {
      $keys = $key_result->getData();
      foreach ($keys as $key) {
        if ($key->getName() === $key_name) {
          $result = $key->getId();
          break;
        }
      }
    } elseif ($key_result->getExitCode() !== 404) {
      // Exit code 404 indicates the resource was not found. That should not apply
      // in this case because the call retrieves all of the SSH keys regardless of
      // the key name, but this check is included here for completeness.
      // The Cloud API call failed.
      throw new \DomainException(
        sprintf('The listSshKeys Acquia Cloud API call failed: %s', $key_result->getExitMessage())
      );
    }
    return $result;
  }

  /**
   * Gets the SSH key name from the associated Hosting SSH key ID.
   *
   * @param int $key_id
   *   The Hosting SSH key ID.
   *
   * @return string|null
   *   The SSH key nickname or NULL if the key is not registered in hosting.
   *
   * @throws \DomainException
   *   If the Cloud API call used to look up the key failed.
   */
  public function getKeyNameFromKeyId($key_id) {
    $result = NULL;
    $cloud = new AcquiaCloud($this->environment, $this->getWipLog());
    $key_result = $cloud->getSshKey($key_id);
    if ($key_result->isSuccess()) {
      $key_info = $key_result->getData();
      $result = $key_info->getName();
    } elseif ($key_result->getExitCode() !== 404) {
      // A 404 exit code indicates the resource was not found.
      // The call failed.
      throw new \DomainException(
        sprintf('The getSshKey AcquiaCloud API call failed: %s', $key_result->getExitMessage())
      );
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function generateWorkId() {
    $key_name = $this->getKeyName();
    if (NULL === $key_name) {
      $key_id = $this->getKeyId();
      if (NULL === $key_id) {
        throw new \DomainException('The key name or ID must be set before generating the work ID.');
      }
      $key_name = sprintf('ssh_key:%d', $key_id);
    }
    $work_id = sprintf('%s:%s', __CLASS__, $key_name);
    return sha1($work_id);
  }

  /**
   * Gets the nickname of the SSH key that will be removed.
   *
   * @return string|null
   *   The key name or NULL if it has not been set.
   */
  public function getKeyName() {
    return $this->keyName;
  }

  /**
   * Sets the nickname of the SSH key to remove from Acquia Hosting.
   *
   * @param string $key_name
   *   The key name.
   */
  private function setKeyName($key_name) {
    $this->keyName = $key_name;
  }

  /**
   * Sets the Hosting ID associated with the SSH key.
   *
   * @param int $key_id
   *   The Hosting ID.
   */
  private function setKeyId($key_id) {
    $this->keyId = $key_id;
  }

  /**
   * Gets the Hosting ID associated with the SSH key.
   *
   * @return int|null
   *   THe Hosting ID or NULL if it has not been set.
   */
  public function getKeyId() {
    return $this->keyId;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    parent::setOptions($options);
    if (!empty($options->keyName)) {
      $this->setKeyName($options->keyName);
    }
    if (!empty($options->keyId)) {
      $this->setKeyId(intval($options->keyId));
    }
  }

}
