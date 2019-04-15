<?php

namespace Acquia\Wip\Test\PrivateStable\Objects;

use Acquia\Wip\Environment;
use Acquia\Wip\IndependentEnvironment;
use Acquia\Wip\Objects\IndependentSite;
use Acquia\Wip\Objects\ParameterDocument;
use Acquia\Wip\Objects\SiteGroup;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;

/**
 * Missing summary.
 */
class ParameterDocumentTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   *
   * @expectedException \Exception
   */
  public function testInstantiateWithNoDocument() {
    @new ParameterDocument(NULL);
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInstantiateWithNonStringValue() {
    new ParameterDocument(15);
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInstantiateWithInvalidJson() {
    new ParameterDocument('');
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   */
  public function testGetProperty() {
    $data = array(
      'item1' => 15,
      'item2' => 'fifteen',
      'item3' => FALSE,
    );
    $pd = new ParameterDocument(json_encode($data));
    $this->assertTrue(is_int($pd->getValue('item1')));
    $this->assertEquals(15, $pd->getValue('item1'));

    $this->assertTrue(is_string($pd->getValue('item2')));
    $this->assertEquals('fifteen', $pd->getValue('item2'));

    $this->assertTrue(is_bool($pd->getValue('item3')));
    $this->assertEquals(FALSE, $pd->getValue('item3'));
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   */
  public function testGetPropertyMagicMethod() {
    $data = array(
      'item1' => 15,
      'item2' => 'fifteen',
      'item3' => FALSE,
    );
    $pd = new ParameterDocument(json_encode($data));
    $this->assertTrue(is_int($pd->item1));
    $this->assertEquals(15, $pd->item1);

    $this->assertTrue(is_string($pd->item2));
    $this->assertEquals('fifteen', $pd->item2);

    $this->assertTrue(is_bool($pd->item3));
    $this->assertEquals(FALSE, $pd->item3);
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   */
  public function testIssetMagicMethod() {
    $data = array(
      'item1' => 15,
      'item2' => 'fifteen',
      'item3' => FALSE,
    );
    $pd = new ParameterDocument(json_encode($data));
    $this->assertTrue(isset($pd->item1));
    $this->assertTrue(isset($pd->item2));
    $this->assertTrue(isset($pd->item3));

    $this->assertFalse(isset($pd->item4));
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   */
  public function testGetPropertyNames() {
    $data = array(
      'item1' => 15,
      'item2' => 'fifteen',
      'item3' => FALSE,
    );
    $pd = new ParameterDocument(json_encode($data));
    $this->assertEquals(0, count(array_diff(array('item1', 'item2', 'item3'), $pd->getPropertyNames())));
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   */
  public function testPropertyTypeConversion() {
    $data = $this->getExampleDocument();
    $pd = new ParameterDocument($data, array('siteGroups' => 'Acquia\Wip\Objects\SiteGroup'));
    $this->assertTrue(is_array($pd->siteGroups));
    $this->assertEquals(1, count($pd->siteGroups));
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   *
   * @expectedException \Exception
   */
  public function testPropertyTypeConversionWithBadConverter() {
    $data = $this->getExampleDocument();
    $pd = new ParameterDocument($data, array('siteGroups' => 'Acquia\Wip\Objects\SiteGroups'));
    $this->assertTrue(is_array($pd->siteGroups));
    $this->assertEquals(1, count($pd->siteGroups));
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   *
   * @group fail
   */
  public function testFromEnvironment() {
    $creds = AcquiaCloudTestSetup::getCreds();
    $env = AcquiaCloudTestSetup::getEnvironment();
    $env_name = AcquiaCloudTestSetup::getProductionEnvironmentName($env);

    $environment = Environment::makeEnvironment(
      AcquiaCloudTestSetup::createWipLog(),
      0,
      $creds->getSitegroup(),
      $creds->getEndpoint(),
      $creds->getUsername(),
      $creds->getPassword(),
      $env_name
    );
    $parameter_doc = ParameterDocument::fromEnvironment(
      $environment,
      array('siteGroups' => 'Acquia\Wip\Objects\SiteGroup')
    );

    $properties = $parameter_doc->getPropertyNames();
    // Make sure there are some properies and it's an array.
    $this->assertTrue(!empty($properties) && is_array($properties));
    // The first (and only) property should be "siteGroups".
    $this->assertEquals($properties[0], 'siteGroups');

    $sitegroups = $parameter_doc->getValue('siteGroups');
    // The first (and only) key should be the sitegroup name.
    $this->assertEquals(key($sitegroups), $creds->getSitegroup());
    $sitegroup = $sitegroups[$creds->getSitegroup()];
    // The sitegroup object should be an instance of SiteGroup.
    $this->assertTrue($sitegroup instanceof \Acquia\Wip\Objects\SiteGroup);

    /** @var SiteGroup $sitegroup */
    // Check the sitegroup object has the right sitegroup name.
    $this->assertEquals($sitegroup->getFullyQualifiedName(), $creds->getSitegroup());
    // Check the sitegroup object has the right environment name.
    $this->assertEquals($sitegroup->getLiveEnvironment(), $env_name);
    // Check that the sites are stored in an array, but do not check empty as an
    // empty array is a valid result. This happens on sfwiptravis for instance.
    $this->assertTrue(is_array($sitegroup->getSites()));

    // Check creds are right. It would be very strange for this to happen since
    // if the creds are wrong, the tests would have failed by now.
    $sitegroup_creds = $sitegroup->getCloudCreds();
    $this->assertEquals($sitegroup_creds->getSitegroup(), $creds->getSitegroup());
    $this->assertEquals($sitegroup_creds->getEndpoint(), $creds->getEndpoint());
    $this->assertEquals($sitegroup_creds->getUsername(), $creds->getUsername());
    $this->assertEquals($sitegroup_creds->getPassword(), $creds->getPassword());

    // Check that the servers are stored in an array, but do not check empty as
    // an empty array is a valid result. This happens on sfwiptravis for
    // instance.
    $this->assertTrue(is_array($sitegroup->getServers()));
  }

  /**
   * Missing summary.
   */
  public function testExtractEnvironment() {
    $data = $this->getExampleDocument();
    $pd = new ParameterDocument($data, array('siteGroups' => 'Acquia\Wip\Objects\SiteGroup'));

    /** @var IndependentEnvironment $environment */
    $environment = $pd->extract(array(
      'siteGroup' => 'lewis',
      'environment' => 'prod',
    ));
    $this->assertInstanceOf('\Acquia\Wip\IndependentEnvironment', $environment);
    $this->assertEquals('lewis', $environment->getSitegroup());
    $this->assertEquals('prod', $environment->getEnvironmentName());
    $this->assertInstanceOf('\Acquia\Wip\AcquiaCloud\CloudCredentials', $environment->getCloudCredentials());
    $this->assertEquals(array(
      "test1.morse.sprint110.acquia-test.com",
      "test2.morse.sprint110.acquia-test.com",
    ), $environment->getPrimaryDomainNames());
  }

  /**
   * Missing summary.
   */
  public function testExtractSite() {
    $data = $this->getExampleDocument();
    $pd = new ParameterDocument($data, array('siteGroups' => 'Acquia\Wip\Objects\SiteGroup'));

    /** @var IndependentSite $site */
    $site = $pd->extract(array(
      'siteGroup' => 'lewis',
      'environment' => 'prod',
      'site' => 91,
    ));

    $this->assertInstanceOf('\Acquia\Wip\Objects\IndependentSite', $site);
    $this->assertEquals(91, $site->getId());
    $this->assertEquals('tuhc91', $site->getDbRole());
    $this->assertEquals(array('test1.morse.sprint110.acquia-test.com'), $site->getDomains());
    $this->assertEquals('test1.morse.sprint110.acquia-test.com', $site->getPrimaryDomainName());
    // @todo - more asserts
  }

  /**
   * Missing summary.
   *
   * @todo - test validation fails on a bad parameter document.
   */
  private static function getExampleDocument() {
    return <<<_E
{
    "siteGroups": {
        "lewis": {
            "cloudCreds": {
                "endpoint": "https://cloudapi.sprint110.ahclouddev.com/v1",
                "pass": "b4c0a3d5de635",
                "user": "lewis"
            },
            "environments": {
                "prod": {
                    "name": "prod",
                    "servers": [
                        {
                            "fqdn": "managed-11.sprint110.ahserversdev.com",
                            "status": true
                        },
                        {
                            "fqdn": "managed-15.sprint110.ahserversdev.com",
                            "status": true
                        }
                    ],
                    "sites": {
                        "91": {
                            "customDomain": "",
                            "dbRole": "tuhc91",
                            "domains": [
                                "test1.morse.sprint110.acquia-test.com"
                            ],
                            "id": 91,
                            "internalDomain": "test1.morse.sprint110.acquia-test.com"
                        },
                        "96": {
                            "customDomain": "",
                            "dbRole": "tuhc96",
                            "domains": [
                                "test2.morse.sprint110.acquia-test.com"
                            ],
                            "id": 96,
                            "internalDomain": "test2.morse.sprint110.acquia-test.com"
                        }
                    },
                    "type": "live_env"
                },
                "update": {
                    "name": "update",
                    "servers": [
                        {
                            "fqdn": "managed-11.sprint110.ahserversdev.com",
                            "status": true
                        },
                        {
                            "fqdn": "managed-15.sprint110.ahserversdev.com",
                            "status": true
                        }
                    ],
                    "sites": [],
                    "type": "update_env"
                }
            },
            "liveEnvironment": "update",
            "multisite": true,
            "name": "lewis",
            "updateEnvironment": "prod"
        }
    }
}
_E;
  }

  /**
   * Gets a parameter document for testing.
   *
   * @return ParameterDocument
   *   The ParameterDocument instance.
   */
  public static function getParameterDocument() {
    $data = self::getExampleDocument();
    $result = new ParameterDocument($data, array('siteGroups' => 'Acquia\Wip\Objects\SiteGroup'));
    return $result;
  }

}
