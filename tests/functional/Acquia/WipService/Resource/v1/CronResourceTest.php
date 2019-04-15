<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Test\AbstractFunctionalTest;

/**
 * Missing summary.
 */
class CronResourceTest extends AbstractFunctionalTest {

  /**
   * Tests the POST method.
   */
  public function testPostAction() {
    $client = $this->createClient('ROLE_ADMIN');
    // Request body is a valid but empty JSON doc.
    $client->request('POST', '/cron?interval=' . urlencode('1 * * * *'), array(), array(), array(), '{}');
    $this->assertTrue($client->getResponse()->isOk());
  }

  /**
   * Tests the *as yet unimplemented* PUT method.
   */
  public function testPutAction() {
    $client = $this->createClient('ROLE_ADMIN');
    // Request body is a valid but empty JSON doc.
    $client->request('PUT', '/cron/1?interval=' . urlencode('1 * * * *'), array(), array(), array(), '{}');
    $this->assertTrue($client->getResponse()->isClientError());
    $this->assertContains(
      'Editing an existing cron configuration is not currently supported',
      (string) $client->getResponse()
    );
  }

}
