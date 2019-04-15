<?php

namespace Acquia\Wip\Test\PrivateStable\Objects;

use Acquia\Wip\AcquiaCloud\CloudCredentials;
use Acquia\Wip\AcquiaCloud\ResultTypes\AcquiaCloudStringArrayResult;
use Acquia\Wip\Environment;
use Acquia\Wip\Objects\ParameterDocumentBuilder;
use Acquia\Wip\Test\Integration\PublicStable\AcquiaCloud\AcquiaCloudTestSetup;

/**
 * Tests the creation of the parameter document using the Cloud API.
 */
class ParameterDocumentBuilderTest extends \PHPUnit_Framework_TestCase {

  /**
   * Missing summary.
   *
   * @var Environment
   *   The Environment to use for testing.
   */
  private $environment;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->environment = new Environment();
    $this->environment->setCloudCredentials($this->getCredentials());
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   * @group Wip
   */
  public function testConstructor() {
    $credentials = $this->getCredentials();
    $builder = new ParameterDocumentBuilder($credentials);
    $this->assertNotNull($builder);
    $this->assertEquals($credentials, $builder->getCredentials());
  }

  /**
   * Missing summary.
   *
   * @group ParameterDocument
   * @group Wip
   */
  public function testSetCredentials() {
    $credentials = $this->getCredentials();
    $builder = new ParameterDocumentBuilder($credentials);
    $builder->setCredentials($credentials);
    $this->assertEquals($credentials, $builder->getCredentials());
  }

  /**
   * Tests building a parameter document with cloud calls.
   *
   * @group ParameterDocument
   * @group Wip
   */
  public function testBuild() {
    $builder = new ParameterDocumentBuilder($this->getCredentials());
    $builder->setCredentials($this->getCredentials());
    $document = $builder->build();
    $this->assertInstanceOf('Acquia\Wip\Objects\ParameterDocument', $document);
  }

  /**
   * Tests building a parameter document with no cloud calls.
   *
   * @group ParameterDocument
   * @group Wip
   */
  public function testBuildNoCloud() {
    $builder = new ParameterDocumentBuilder($this->getCredentials());
    $builder->setCredentials($this->getCredentials());
    $builder->setCloudCalls(FALSE);
    $document = $builder->build();
    $this->assertInstanceOf('Acquia\Wip\Objects\ParameterDocument', $document);
  }

  /**
   * Tests that an AcquiaCloudApiException is thrown if a Cloud API call fails.
   *
   * @group ParameterDocument
   * @group Wip
   *
   * @expectedException \Acquia\Wip\Exception\AcquiaCloudApiException
   */
  public function testUnsuccessfulCloudApiCall() {
    // Create a mock ParameterDocumentBuilder with a failure response for
    // testing.
    $failure_response = new AcquiaCloudStringArrayResult();
    $failure_response->setExitMessage('Exit message');
    $failure_response->setExitCode(500);

    $mock_cloud = $this->getMock('Acquia\Wip\AcquiaCloud\AcquiaCloud', array('listEnvironments'));
    $mock_cloud->expects($this->any())->method('listEnvironments')->willReturn($failure_response);

    $mock_builder = $this->getMock(
      'Acquia\Wip\Objects\ParameterDocumentBuilder',
      array('getAcquiaCloud'),
      array($this->getCredentials())
    );
    $mock_builder->expects($this->any())->method('getAcquiaCloud')->willReturn($mock_cloud);

    $mock_builder->buildJson();
  }

  /**
   * Gets the Acquia Cloud credentials.
   *
   * @return CloudCredentials
   *   The Acquia Cloud credentials.
   */
  private function getCredentials() {
    return AcquiaCloudTestSetup::getCreds();
  }

}
