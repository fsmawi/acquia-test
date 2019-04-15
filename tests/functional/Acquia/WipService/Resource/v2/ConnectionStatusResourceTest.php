<?php

namespace Acquia\WipService\Resource\v2;

use Acquia\WipService\Test\AbstractFunctionalTest;

/**
 * Tests that ConnectionStatusResource performs as expected.
 */
class ConnectionStatusResourceTest extends AbstractFunctionalTest {

  /**
   * Tests that the resource responds appropriately.
   */
  public function testGetConnectionStatusResource() {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', '/v2/connection-status');
    $response = $client->getResponse();

    // Check that we got the expected 200 status code.
    $this->assertEquals(200, $response->getStatusCode());

    // Check that expected fields exist in the response body.
    $content = json_decode($response->getContent());
    $this->assertObjectHasAttribute('wip', $content);
    $this->assertEquals('OK', $content->wip->status);

    $this->assertObjectHasAttribute('system', $content);
    $this->assertEquals('OK', $content->system->status);

    $this->assertObjectHasAttribute('details', $content->wip);
    $this->assertNotEmpty($content->wip->details);
    // Access the sub element it has spaces so this makes it nicer to read...
    $name = 'Container version';
    $this->assertNotEmpty($content->wip->details->{$name});
  }

}
