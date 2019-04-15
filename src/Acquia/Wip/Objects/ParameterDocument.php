<?php

namespace Acquia\Wip\Objects;

use Acquia\Wip\EnvironmentInterface;

/**
 * The ParameterDocument provides parameter access that affect Wip execution.
 */
class ParameterDocument implements \JsonSerializable {

  /**
   * The document data.
   *
   * @var object
   */
  private $data = NULL;

  /**
   * Creates a new ParameterDocument initialized with the specified json data.
   *
   * @param string $json_document
   *   The document, in JSON format.
   * @param ParameterConverterInterface[] $converters
   *   Optional. An associative array indicating any data converters that should
   *   be applied when decoding the JSON document.  The $converters array should
   *   use a key that indicates the property name, associated with a string
   *   value that indicates the class that will do the proper conversion. The
   *   specified class must implement the ParameterConverterInterface interface.
   *
   * @throws \InvalidArgumentException
   *   If the specified JSON document is not properly formed or any of the
   *   converters does not implement the ParameterConverterInterface interface.
   */
  public function __construct($json_document, $converters = array()) {
    if (!is_string($json_document)) {
      throw new \InvalidArgumentException('The json_document argument must be a string.');
    }
    $data = json_decode($json_document);
    if ($data === NULL) {
      throw new \InvalidArgumentException('The json_document argument must be valid JSON.');
    }
    $this->convertTypes($data, $converters);
    $this->data = $data;
  }

  /**
   * Converts a parameter document type to its intended PHP type.
   *
   * @param object $object
   *   The object to convert.
   * @param ParameterConverterInterface[] $converters
   *   The array of converters that will change the type of specific fields in
   *   the object being converted.
   */
  private function convertTypes($object, $converters) {
    foreach ($object as $key => $value) {
      if (in_array($key, array_keys($converters))) {
        $converter_classname = $converters[$key];
        $converter_class = new \ReflectionClass($converter_classname);
        if ($converter_class->implementsInterface('Acquia\Wip\Objects\ParameterConverterInterface')) {
          $object->$key = $converter_classname::convert($object->$key);
        } else {
          $message = 'The "%s" converter for property "%s" does not implement the ParameterConverter interface. (class is "%s")';
          throw new \InvalidArgumentException(sprintf($message, $converter_class, $key, get_class($converter_class)));
        }
      }
    }
  }

  /**
   * Extracts a section from this document and adds parent data to the section.
   *
   * This can be used to extract either a single sitegroup, an environment, or a
   * site from the document. Any context data needed from the parent objects
   * will propagate onto the returned object.
   *
   * @param array $keys
   *
   *   An associative array of keys to find the required section.  Allowed keys
   *   are "siteGroup", "environment", "site".  Examples:
   *     - find a single siteGroup: array('siteGroup' => 'my_site_group')
   *     - find a single environment: array(
   *         'siteGroup' => 'my_site_group',
   *         'environment' => 'prod',
   *       )
   *     - find a single site: array(
   *         'siteGroup' => 'my_site_group',
   *         'environment' => 'prod',
   *         'site' => 1,
   *       ).
   *
   * @return mixed
   *   The requested object, or NULL on failure.
   */
  public function extract($keys) {
    $result = NULL;
    // @TODO - propagation of "unknown" properties is incomplete.
    // @TODO - allow * as a key value to get a list of all of a list part
    $sitegroup = $keys['siteGroup'];
    unset($keys['siteGroup']);
    // @TODO - throw if nonexistent.
    // @TODO - context must not initially contain either siteGroup, environment or site:
    // that would overwrite internal values
    // Allow any unknown properties to be propagated:
    // @TODO - needs param checking so we don't overwrite props. Might be ok.
    $context = array_diff_key((array) $this->data, array('siteGroups' => TRUE));
    return $this->data->siteGroups[$sitegroup]->extract($keys, $context);
  }

  /**
   * Returns the value associated with the specified name.
   *
   * @param string $name
   *   The name of the desired value.
   *
   * @return mixed
   *   The value associated with the specified name.
   */
  public function getValue($name) {
    $result = NULL;
    if (isset($this->data->$name)) {
      $result = $this->data->$name;
    }
    return $result;
  }

  /**
   * Magic getter.
   *
   * @param string $name
   *   The name of the property.
   *
   * @return mixed
   *   The value of the property.
   */
  public function __get($name) {
    return $this->getValue($name);
  }

  /**
   * Magic property isset check.
   *
   * @param string $name
   *   The name of the property.
   *
   * @return bool
   *   Whether or not the property is set.
   */
  public function __isset($name) {
    return isset($this->data->$name);
  }

  /**
   * Gets the names of all properties stored in this instance.
   *
   * @return string[]
   *   An array of property names.
   */
  public function getPropertyNames() {
    return array_keys((array) $this->data);
  }

  /**
   * Creates a parameter document from the specified Environment.
   *
   * @param EnvironmentInterface $environment
   *   The environment.
   * @param ParameterConverterInterface[] $converters
   *   Optional. An associative array indicating any data converters that should
   *   be applied when decoding the JSON document.  The $converters array should
   *   use a key that indicates the property name, associated with a string
   *   value that indicates the class that will do the proper conversion. The
   *   specified class must implement the Acquia\Wip\Objects\ParameterConverterInterface
   *   interface.
   *
   * @throws \InvalidArgumentException
   *   If the specified JSON document is not properly formed or any of the
   *   converters does not implement the ParameterConverterInterface interface.
   *
   * @return ParameterDocument
   *   The ParameterDocument instance.
   */
  public static function fromEnvironment(EnvironmentInterface $environment, $converters = array()) {
    $obj = new \stdClass();
    $obj->siteGroups = new \stdClass();

    $sitegroup = $environment->getFullyQualifiedSitegroup();
    $sitegroup_obj = new \stdClass();

    $cloud_creds = $environment->getCloudCredentials();
    $sitegroup_obj->name = $sitegroup;
    $sitegroup_obj->cloudCreds = new \stdClass();
    $sitegroup_obj->cloudCreds->endpoint = $cloud_creds->getEndpoint();
    $sitegroup_obj->cloudCreds->user = $cloud_creds->getUsername();
    $sitegroup_obj->cloudCreds->pass = $cloud_creds->getPassword();

    $environment_name = $environment->getEnvironmentName();
    $sitegroup_obj->liveEnvironment = $environment_name;
    $sitegroup_obj->updateEnvironment = $environment_name;
    $environments = new \stdClass();
    $environments->$environment_name = new \stdClass();
    $environments->$environment_name->name = $environment_name;

    $servers = $environment->getServers();
    $server_array = array();
    foreach ($servers as $server) {
      $server_obj = new \stdClass();
      $server_obj->active = TRUE;
      $server_obj->fqdn = $server;
      $server_array[] = $server_obj;
    }
    $sites = $environment->getSites();
    $sites_obj = new \stdClass();
    foreach ($sites as $site) {
      $site_obj = new \stdClass();
      $domains = $site->getDomains();
      $site_obj->customDomain = '';
      $site_obj->dbRole = $site->getDbRole();
      $site_obj->domains = $domains;
      $id = $site->getId();
      $site_obj->id = $id;
      $site_obj->internalDomain = '';
      $sites_obj->$id = $site_obj;
    }

    $environments->$environment_name->servers = $server_array;
    $environments->$environment_name->sites = $sites_obj;
    $sitegroup_obj->environments = $environments;
    $obj->siteGroups->$sitegroup = $sitegroup_obj;
    return new ParameterDocument(json_encode($obj), $converters);
  }

  /**
   * Creates an object capable of json encoding.
   *
   * @todo: This is not complete.
   */
  public function jsonSerialize() {
    $obj = new \stdClass();
    $obj->siteGroups = array();

    foreach ($this->data->siteGroups as $sitegroup) {
      $sg_obj = new \stdClass();
      foreach ($sitegroup as $property => $value) {
        $sg_obj->property = $value;
      }
      $obj->siteGroups[] = $sg_obj;
    }
    return $obj;

    /*
    $sitegroup = $environment->getSitegroup();
    $sitegroup_obj = new \stdClass();

    $cloud_creds = $environment->getCloudCredentials();
    $sitegroup_obj->name = $sitegroup;
    $sitegroup_obj->cloudCreds = new \stdClass();
    $sitegroup_obj->cloudCreds->endpoint = $cloud_creds->getEndpoint();
    $sitegroup_obj->cloudCreds->user = $cloud_creds->getUsername();
    $sitegroup_obj->cloudCreds->pass = $cloud_creds->getPassword();

    $environment_name = $environment->getEnvironmentName();
    $sitegroup_obj->liveEnvironment = $environment_name;
    $sitegroup_obj->updateEnvironment = $environment_name;
    $environments = new \stdClass();
    $environments->$environment_name = new \stdClass();
    $environments->$environment_name->name = $environment_name;

    $servers = $environment->getServers();
    $server_array = array();
    foreach ($servers as $server) {
    $server_obj = new \stdClass();
    $server_obj->active = TRUE;
    $server_obj->fqdn = $server;
    $server_array[] = $server_obj;
    }
    $sites = $environment->getSites();
    $sites_obj = new \stdClass();
    foreach ($sites as $site) {
    $site_obj = new \stdClass();
    $domains = $site->getDomains();
    $site_obj->customDomain = '';
    $site_obj->dbRole = $site->getDbRole();
    $site_obj->domains = $domains;
    $id = $site->getId();
    $site_obj->id = $id;
    $site_obj->internalDomain = '';
    $sites_obj->$id = $site_obj;
    }

    $environments->$environment_name->servers = $server_array;
    $environments->$environment_name->sites = $sites_obj;
    $sitegroup_obj->environments = $environments;
    $obj->siteGroups->$sitegroup = $sitegroup_obj;
    return json_encode($obj);
     */
  }

}
