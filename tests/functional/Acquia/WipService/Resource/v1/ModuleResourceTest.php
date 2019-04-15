<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipIntegrations\DoctrineORM\WipModuleStore;
use Acquia\WipService\Test\AbstractFunctionalTest;
use Acquia\Wip\WipFactory;
use Teapot\StatusCode;

/**
 * Tests ModuleResource.
 */
class ModuleResourceTest extends AbstractFunctionalTest {

  /**
   * The Wip module storage instance.
   *
   * @var WipModuleStore
   */
  private $moduleStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    WipFactory::setConfigPath('config/config.factory.test.cfg');
    $this->moduleStorage = WipModuleStore::getWipModuleStore();
  }

  /**
   * Tests the POST method by adding a module.
   */
  public function testPostActionParameters() {
    $request_body = (object) array(
      'name' => 'name',
      'vcs-uri' => 'vcs_uri',
      'commit-tag' => 'commit_tag',
      'enabled' => 0,
    );
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('POST', '/modules', array(), array(), array(), json_encode($request_body));
    $response = $client->getResponse();

    // Check we got the expected status code.
    $this->assertEquals(200, $response->getStatusCode());
    $result = json_decode($response->getContent());
    $this->assertSame(TRUE, $result->successful);
  }

  /**
   * Tests that users with non-admin level access cannot add modules.
   */
  public function testNonAdminAddModules() {
    $request_body = (object) array(
      'name' => 'name',
      'vcs-uri' => 'vcs_uri',
      'commit-tag' => 'commit_tag',
      'enabled' => 0,
    );
    $client = $this->createClient('ROLE_USER');
    $client->request('POST', '/modules', array(), array(), array(), json_encode($request_body));
    $response = $client->getResponse();

    // Check we got the expected status code and error message.
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals(
      "Access to this resource is restricted.",
      json_decode($response->getContent(), TRUE)['message']
    );
  }

}
