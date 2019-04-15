<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\App;
use Acquia\WipService\Exception\InternalServerErrorException;
use Acquia\WipService\Http\HalResponse;
use Acquia\WipService\Resource\AbstractResource;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Teapot\StatusCode;

/**
 * Provides REST API endpoints for interacting with Build Steps.
 */
class BuildStepsResource extends AbstractResource {

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array();
  }

  /**
   * Retrieves the public key to be used to encrypt data in the build yaml file.
   *
   * The encoding keys are used to enable secure data exchange between the
   * customer and the Build Steps job via the yaml file. If the encoding keys
   * are not yet generated, then it will be created when it's first requested.
   *
   * @param Request $request
   *   An instance of Request representing the HTTP request.
   * @param Application $app
   *   An instance of Application representing the application.
   *
   * @return HalResponse
   *   An instance of HalResponse representing the HTTP response.
   *
   * @deprecated
   */
  public function getPublicKeyAction(Request $request, Application $app) {
    $app['segment']->track([
      'userId' => $request->getUser(),
      'event' => 'Get public key',
      'context' => [
        'ip' => $request->getClientIp(),
        'userAgent' => $request->headers->get('User-Agent'),
      ],
    ]);

    // MS-2043 removes the ability for the client to encrypt data. There is
    // no furhter need to retrieve the public key.
    $response = array(
      'message' => 'Retrieving the public key is no longer supported. Please update your client.',
    );
    $hal_response = new HalResponse($app['hal']($request->getUri(), $response));
    $hal_response->setStatusCode(490);
    return $hal_response;
  }

}
