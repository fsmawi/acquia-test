<?php

namespace Acquia\Wip\Drupal;

use Acquia\Wip\Environment;

/**
 * Represents a Drupal site.
 */
class DrupalSite extends Environment {

  /**
   * The site domains.
   *
   * @var string[]
   */
  private $domains = array();

  /**
   * The current domain, which will be used for any drush operations.
   *
   * @var string
   */
  private $currentDomain = NULL;

  /**
   * Creates a new instance of DrupalSite.
   *
   * @param string[] $domains
   *   An array of domains to add.
   */
  public function __construct($domains) {
    foreach ($domains as $domain) {
      $this->addDomain($domain);
    }
    if (count($this->domains) > 0) {
      $current_domain = end($this->domains);
      reset($this->domains);
      $this->setCurrentDomain($current_domain);
    }
  }

  /**
   * Adds the specified domain to this DrupalSite instance.
   *
   * @param string $domain
   *   The domain name.
   */
  public function addDomain($domain) {
    if (!self::validateDomain($domain)) {
      throw new \InvalidArgumentException(sprintf(
        'The domain argument must be a valid URL; "%s" is not valid.',
        $domain
      ));
    }
    $this->domains[] = $domain;
  }

  /**
   * Gets the set of domains.
   *
   * @return string[]
   *   The set of domains.
   */
  public function getDomains() {
    return $this->domains;
  }

  /**
   * Sets the current domain.
   *
   * @param string $current_domain
   *   The current domain.
   *
   * @throws \InvalidArgumentException
   *   If the current domain does not exist in the set of domains.
   */
  public function setCurrentDomain($current_domain) {
    if (in_array($current_domain, $this->domains)) {
      $this->currentDomain = $current_domain;
    } else {
      throw new \InvalidArgumentException('The current_domain argument must be in the set of domains.');
    }
  }

  /**
   * Gets the current domain.
   *
   * @return string
   *   The current domain.
   */
  public function getCurrentDomain() {
    return $this->currentDomain;
  }

  /**
   * Validates the specified domain name.
   *
   * @param string $domain
   *   The domain name to validate.
   *
   * @return bool
   *   TRUE if the specified domain name is valid; FALSE otherwise.
   */
  public static function validateDomain($domain) {
    // Turn the domain into a URL.
    $url = sprintf('http://%s', $domain);
    return filter_var($url, FILTER_VALIDATE_URL);
  }

}
