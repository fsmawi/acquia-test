<?php

namespace Acquia\WipService\Resource\v1;

use Acquia\WipService\Resource\AbstractResource;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource to retrieve documentation for the API.
 */
class DocumentationResource extends AbstractResource {

  /**
   * Implements DependencyManagedInterface::getDependencies().
   */
  public function getDependencies() {
    return array();
  }

  /**
   * Returns HTML documentation for the API.
   */
  public function getAction(Request $request, Application $app) {
    $api_version = $this->getApiVersion();
    // Generate twig template variables.
    $groups = array();
    foreach ($app['api.versions'][$api_version]['operations'] as $route_name => $route) {
      // Skip hidden routes.
      if (!empty($route['hidden'])) {
        continue;
      }

      $data = array(
        'name'       => $route_name,
        'method'     => $route['httpMethod'],
        'uri'        => $route['uri'],
        'summary'    => $route['summary'],
        'parameters' => array(),
      );
      if (!empty($route['parameters'])) {
        foreach ($route['parameters'] as $parameter_name => $parameter) {
          $data['parameters'][$parameter_name] = array(
            'name'        => $parameter_name,
            'description' => $parameter['description'],
            'location'    => $parameter['location'],
            'type'        => $parameter['type'],
            'required'    => !empty($parameter['required']) ? 'true' : 'false',
          );
        }
      }
      $groups[$route['group']]['title'] = $route['group'];
      $groups[$route['group']]['routes'][$route_name] = $data;
    }

    return $app['twig']->render('documentation.twig', array(
      'version' => $app['api.versions'][$api_version]['apiVersion'],
      'groups' => $groups,
    ));
  }

}
