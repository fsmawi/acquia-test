<?php

namespace Acquia\Wip\Objects;

use Acquia\Wip\Exception\InvalidSiteException;

/**
 * The SiteRelationship class represents site relationships within a sitegroup.
 */
class Site implements ParameterConverterInterface {

  /**
   * The name of the site group.
   *
   * @var string
   */
  protected $siteGroup = NULL;

  /**
   * The database role name.
   *
   * @var string
   */
  protected $dbRole = NULL;

  /**
   * The domain names associated with this site.
   *
   * @var string[]
   */
  protected $domains = array();

  /**
   * The ID associated with this site.
   *
   * @var int
   */
  protected $id = NULL;

  /**
   * The internal domain name for this site instance.
   *
   * @var string
   */
  protected $internalDomain = NULL;

  /**
   * The preferred custom domain name associated with this site.
   *
   * @var string
   */
  protected $customDomain = NULL;

  /**
   * Creates a new instance of Site.
   *
   * @param string $site_group
   *   The name of the sitegroup associated with this site instance.
   * @param string $db_role
   *   The database role name associated with this site instance.
   * @param string[] $domains
   *   The set of domain names associated with this site instance.
   * @param int $id
   *   The site ID.
   */
  public function __construct($site_group, $db_role, $domains, $id = NULL) {
    $this->siteGroup = $site_group;
    $this->dbRole = $db_role;
    $this->domains = $domains;
    $this->id = $id;
  }

  /**
   * Converts the specified value to an appropriate object.
   *
   * @param mixed $value
   *   The value to convert.
   *
   * @return object
   *   The value converted into an appropriate object.
   */
  public static function convert($value, $context = array()) {
    $result = array();
    foreach ($value as $site) {
      $site_obj = new Site($context['siteGroup'], $site->dbRole, $site->domains, $site->id);
      $site_obj->internalDomain = $site->internalDomain;
      $site_obj->customDomain = $site->customDomain;
      $result[$site_obj->getId()] = $site_obj;
    }

    return $result;
  }

  /**
   * Extracts an IndependentSite from this object.
   *
   * @param array $keys
   *   A list of keys.
   * @param array $context
   *   The context to pass to IndependentSite::_construct().
   *
   * @return IndependentSite
   *   The IndependentSite object.
   *
   * @throws InvalidSiteException
   *   If no valid IndependentSite could be created.
   */
  public function extract($keys, $context = array()) {
    // Assume there are no more keys at this point.  If in future anything is
    // nested below the "site" level, then there may be more.
    $site = new IndependentSite($this, $context);
    if (!$site->validate()) {
      throw new InvalidSiteException('Site failed validation.');
    }
    return $site;
  }

  /**
   * Validates the object.
   *
   * @return bool
   *   Whether or not the object is valid.
   */
  public function validate() {
    // @TODO - more validation?
    foreach (array('siteGroup', 'dbRole', 'domains', 'id') as $member) {
      if (empty($this->$member)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Returns the site group name.
   *
   * @return string
   *   The name of the site group.
   */
  public function getSiteGroup() {
    return $this->siteGroup;
  }

  /**
   * Set the name of the site group associated with this site instance.
   *
   * @param string $site_group
   *   The site group name.
   */
  public function setSiteGroup($site_group) {
    $this->siteGroup = $site_group;
  }

  /**
   * Returns the database role name associated with this site.
   *
   * @return string
   *   The database role name.
   */
  public function getDbRole() {
    return $this->dbRole;
  }

  /**
   * Sets the database role name associated with this site.
   *
   * @param string $db_role
   *   The database role name.
   */
  public function setDbRole($db_role) {
    $this->dbRole = $db_role;
  }

  /**
   * Returns the set of domain names associated with this site instance.
   *
   * @return string[]
   *   The domain names.
   */
  public function getDomains() {
    return $this->domains;
  }

  /**
   * Sets the domain names associated with this site instance.
   *
   * @param string[] $domains
   *   The domain names.
   */
  public function setDomains($domains) {
    $this->domains = $domains;
  }

  /**
   * Gets the site ID.
   *
   * @return int
   *   The site ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Sets the site ID associated with this site instance.
   *
   * @param int $id
   *   The site ID.
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Returns the preferred domain name for this site.
   *
   * @return string
   *   The domain name.
   */
  public function getPrimaryDomainName() {
    $result = NULL;
    if (!empty($this->customDomain)) {
      $result = $this->customDomain;
    } elseif (!empty($this->internalDomain)) {
      $result = $this->internalDomain;
    } elseif (!empty($this->domains)) {
      $result = $this->domains[0];
    }
    return $result;
  }

}
