<?php

namespace Acquia\Wip\AcquiaCloud\DataTypes;

use Acquia\Cloud\Api\Response\Server;
use Acquia\Wip\AcquiaCloud\AcquiaCloudDataType;

/**
 * This class represents a single server instance.
 */
class AcquiaCloudServerInfo extends AcquiaCloudDataType implements \JsonSerializable {

  /**
   * The brief server name.
   *
   * @var string
   */
  private $name;

  /**
   * The fully qualified domain name for the server.
   *
   * @var string
   */
  private $fullyQualifiedDomainName;

  /**
   * The AMI type.
   *
   * @var string
   */
  private $amiType;

  /**
   * The region.
   *
   * @var string
   */
  private $region;

  /**
   * The availability zone.
   *
   * @var string
   */
  private $ec2AvailabilityZone;

  /**
   * The services exposed by this server.
   *
   * @var array
   */
  private $services = array();

  /**
   * Creates a new instance initialized with the specified values.
   *
   * @param Server $server
   *   The server instance from a Cloud SDK call.
   */
  public function __construct(Server $server) {
    $this->setName($server->name());
    $this->setFullyQualifiedDomainName($server->fqdn());
    $this->setAmiType($server->amiType());
    $this->setRegion($server->region());
    $this->setAvailabilityZone($server->availabilityZone());
    $this->setServices($server->services());
  }

  /**
   * Sets the short server name.
   *
   * @param string $name
   *   The short server name.
   */
  private function setName($name) {
    if (!is_string($name) || empty($name)) {
      throw new \InvalidArgumentException('The name parameter must be a non-empty string.');
    }
    $this->name = $name;
  }

  /**
   * Gets the short server name.
   *
   * @return string
   *   The name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the fully qualified domain name.
   *
   * @param string $domain_name
   *   The domain name.
   */
  protected function setFullyQualifiedDomainName($domain_name) {
    if (!is_string($domain_name) || empty($domain_name)) {
      throw new \InvalidArgumentException('The domain_name parameter must be a non-empty string.');
    }
    $this->fullyQualifiedDomainName = $domain_name;
  }

  /**
   * Gets the fully qualified domain name.
   *
   * @return string
   *   the domain name.
   */
  public function getFullyQualifiedDomainName() {
    return $this->fullyQualifiedDomainName;
  }

  /**
   * Sets the server AMI type.
   *
   * @param string $type
   *   The AMI type.
   */
  protected function setAmiType($type) {
    if (!is_string($type) || empty($type)) {
      throw new \InvalidArgumentException('The type parameter must be a non-empty string.');
    }
    $this->amiType = $type;
  }

  /**
   * Gets the server AMI type.
   *
   * @return string
   *   The AMI type.
   */
  public function getAmiType() {
    return $this->amiType;
  }

  /**
   * Sets the server's region.
   *
   * @param string $region
   *   The region.
   */
  protected function setRegion($region) {
    if (!is_string($region) || empty($region)) {
      throw new \InvalidArgumentException('The region parameter must be a non-empty string.');
    }
    $this->region = $region;
  }

  /**
   * Gets the server's region.
   *
   * @return string
   *   The region.
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * Sets the availability zone.
   *
   * @param string $zone
   *   The availability zone.
   */
  protected function setAvailabilityZone($zone) {
    if (!is_string($zone) || empty($zone)) {
      throw new \InvalidArgumentException('The zone parameter must be a non-empty string.');
    }
    $this->ec2AvailabilityZone = $zone;
  }

  /**
   * Gets the server's availability zone.
   *
   * @return string
   *   The availability zone.
   */
  public function getAvailabilityZone() {
    return $this->ec2AvailabilityZone;
  }

  /**
   * Sets the services and service properties exposed by this server.
   *
   * @param array $services
   *   The services.
   */
  protected function setServices($services) {
    if (!is_array($services)) {
      throw new \InvalidArgumentException('The services parameter must be an array.');
    }
    $this->services = $services;
  }

  /**
   * Gets the services exposed by this server.
   *
   * @return array
   *   The services.
   */
  public function getServices() {
    return $this->services;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $result = array(
      'name' => $this->name,
      'fqdn' => $this->fullyQualifiedDomainName,
      'ami_type' => $this->amiType,
      'region' => $this->region,
      'ec2_availability_zone' => $this->ec2AvailabilityZone,
      'services' => $this->services,
    );
    return (object) $result;
  }

}
