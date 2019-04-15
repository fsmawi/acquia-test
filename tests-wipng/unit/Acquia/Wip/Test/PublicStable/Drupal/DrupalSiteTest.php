<?php

namespace Acquia\Wip\Test\PublicStable\Drupal;

use Acquia\Wip\Drupal\DrupalSite;

/**
 * Missing summary.
 */
class DrupalSiteTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testConstructor() {
    $domains = array('domain1', 'domain2', 'domain3');
    $drupal_site = new DrupalSite($domains);

    $this->assertEquals(array_values($domains), array_values($drupal_site->getDomains()));
    $this->assertEquals('domain3', $drupal_site->getCurrentDomain());
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   */
  public function testCurrentDomainName() {
    $domains = array('domain1', 'domain2', 'domain3');
    $drupal_site = new DrupalSite($domains);
    $drupal_site->setCurrentDomain('domain2');
    $this->assertEquals('domain2', $drupal_site->getCurrentDomain());
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testCurrentDomainInvalid() {
    $domains = array('domain1', 'domain2', 'domain3');
    $drupal_site = new DrupalSite($domains);
    $drupal_site->setCurrentDomain('domain4');
  }

  /**
   * Missing summary.
   *
   * @group DrupalSsh
   *
   * @expectedException \InvalidArgumentException
   */
  public function testAddInvalidDomain() {
    $domain = 'testing!';
    $domains = array('domain1', 'domain2', 'domain3');
    $drupal_site = new DrupalSite($domains);
    $drupal_site->addDomain($domain);
  }

}
