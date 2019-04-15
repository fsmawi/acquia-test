<?php

namespace Acquia\WipService\Http;

use Acquia\WipService\App;
use Nocarrier\Hal;
use Symfony\Component\HttpFoundation\Response;

/**
 * Represents a HAL-style HTTP response.
 *
 * HAL (Hypertext Application Language) provides a way to make the API more
 * discoverable by encoding meta data into the response so that client can do
 * advanced discovery behavior.
 */
class HalResponse extends Response {

  /**
   * Send response as JSON.
   */
  const AS_JSON = 10;

  /**
   * Send response as XML.
   */
  const AS_XML = 20;

  /**
   * The response data.
   *
   * @var Hal
   */
  protected $data;

  /**
   * How to encode the response. One of self::AS_JSON or self::AS_XML.
   *
   * @var int
   */
  protected $format;

  /**
   * Creates a new instance of HalResponse.
   *
   * @param Hal $data
   *   An instance of Hal representing the response data.
   * @param int $status
   *   The HTTP status to send in the response.
   * @param array $headers
   *   An array of additional HTTP headers to send in the response.
   */
  public function __construct(Hal $data = NULL, $status = 200, array $headers = array()) {
    parent::__construct('', $status, $headers);
    $this->setData($data);
  }

  /**
   * Factory method to help create a HalResponse object.
   *
   * @param mixed $content
   *   An instance of Hal representing the response data. Since the super method
   *   is required to be compatible with HttpFoundation\Response, we must check
   *   that the argument is of the expected type in the method body, rather than
   *   relying on type-hinting.
   * @param int $status
   *   The HTTP status to send in the response.
   * @param array $headers
   *   An array of additional HTTP headers to send in the response.
   *
   * @return HalResponse
   *   An instance of HalResponse.
   */
  public static function create($content = '', $status = 200, $headers = array()) {
    if (!$content instanceof Hal) {
      throw new \InvalidArgumentException('The content argument must be of type \Nocarrier\Hal');
    }
    return parent::create($content, $status, $headers);
  }

  /**
   * Retrieves the embedded Hal data object.
   *
   * @return Hal
   *   An instance of Hal representing the response data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Sets the embedded Hal data object.
   *
   * @param Hal $data
   *   An instance of Hal representing the response data.
   *
   * @return Response
   *   An instance of Response.
   */
  public function setData(Hal $data = NULL) {
    $this->data = $data;
    return $this->update();
  }

  /**
   * Sets the response format.
   *
   * @param int $format
   *   An integer ID representing the format of the response.
   *
   * @return Response
   *   An instance of Response.
   */
  public function setFormat($format = self::AS_JSON) {
    $this->format = $format;
    return $this->update();
  }

  /**
   * Updates the content according to the data and format.
   *
   * @return Response
   *   An instance of Response.
   */
  protected function update() {
    $content = NULL;
    switch ($this->format) {
      case NULL:
      case self::AS_JSON:
        $mime = 'application/hal+json';
        if ($this->data) {
          $content = $this->data->asJSON();
        }
        break;

      case self::AS_XML:
        $mime = 'application/hal+xml';
        if ($this->data) {
          $content = $this->data->asXML();
        }
        break;

      default:
        throw new \InvalidArgumentException(sprintf(
          'Invalid format given "%d"',
          $this->format
        ));
    }

    $this->headers->set('Content-Type', $mime);
    return $this->setContent($content);
  }

  /**
   * Adds paging-related link relations.
   *
   * @param int $current_page
   *   The current page number being requested.
   * @param int $limit
   *   The number of items being requested.
   * @param int $total
   *   The total number of existing items in the collection.
   */
  public function addPagingLinks($current_page, $limit, $total) {
    $this->addPagingLink('first', 1);
    if ($current_page > 1) {
      $this->addPagingLink('prev', $current_page - 1);
    }
    if ($limit * $current_page < $total) {
      $this->addPagingLink('next', $current_page + 1);
    }
    $this->addPagingLink('last', ceil($total / $limit));
  }

  /**
   * Adds an individual link relation.
   *
   * @param string $name
   *   The name of the relationship.
   * @param int $page
   *   The current page number being requested.
   */
  protected function addPagingLink($name, $page) {
    $request = App::getApp()['request'];
    $query = $request->query->all();
    $query['page'] = $page;
    $hal = $this->getData();
    $uri = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
    $query_string = $request->normalizeQueryString(
      http_build_query($query, '', '&')
    );
    $hal->addLink($name, $uri . '?' . $query_string);
    $this->setData($hal);
  }

}
