<?php

namespace Acquia\WipService\Resource\v2;

use Acquia\WipService\Test\AbstractFunctionalTest;

/**
 * Tests that PingResource performs as expected.
 */
class PingResourceTest extends AbstractFunctionalTest {

  /**
   * Tests that each version of ping resource responds appropriately.
   *
   * @param string $url
   *   The routing URL to the ping resource.
   * @param string $api_version
   *   The expected API version.
   * @param bool $latest
   *   Whether the request was against the latest version of the API.
   *
   * @dataProvider pingVersionProvider
   */
  public function testPingAllVersions($url, $api_version, $latest) {
    $client = $this->createClient('ROLE_ADMIN');
    $client->request('GET', $url);
    $response = $client->getResponse();

    // Check that we got the expected 200 status code.
    $this->assertEquals(200, $response->getStatusCode());

    // Check that expected fields exist in the response body.
    $content = json_decode($response->getContent());
    $this->assertObjectHasAttribute('api_version', $content);
    $this->assertObjectHasAttribute('latest', $content);

    $this->assertSame($api_version, $content->api_version);
    $this->assertSame($latest, $content->latest);
  }

  /**
   * Provides parameters for testing API versioning.
   *
   * @return array
   *   A multi-dimensional array of parameters.
   */
  public function pingVersionProvider() {
    return array(
      array('/ping', 'v1', FALSE),
      array('/v1/ping', 'v1', FALSE),
      array('/v2/ping', 'v2', TRUE),
    );
  }

}
