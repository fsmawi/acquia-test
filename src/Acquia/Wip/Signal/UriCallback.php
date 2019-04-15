<?php

namespace Acquia\Wip\Signal;

use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Security\AuthenticationInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;
use GuzzleHttp\Client as HttpClient;

/**
 * The UriCallback class is responsible for posting a callback to a URL.
 */
class UriCallback extends CallbackBase implements CallbackInterface, DependencyManagedInterface {

  /**
   * The fully-formed URL to which the callback will be sent.
   *
   * @var string
   */
  private $url;

  /**
   * The Wip log instance.
   *
   * @var WipLogInterface
   */
  private $wipLog;

  /**
   * The authentication that will be used when invoking the callback URI.
   *
   * @var AuthenticationInterface
   */
  private $authentication = NULL;

  /**
   * Creates a new instance of UriCallback.
   *
   * @param string $url
   *   The URL to which the callback will be sent.
   */
  public function __construct($url) {
    $dependency_manager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (!empty($dependencies)) {
      $dependency_manager->addDependencies($this->getDependencies());
    }

    // Be sure the URL starts with the protocol.
    if (!$this->hasProtocol($url)) {
      $url = 'https://' . $url;
    }
    $this->url = $url;
  }

  /**
   * Indicates whether the URL starts with a protocol string.
   *
   * @param string $url
   *   The URL.
   *
   * @return bool
   *   TRUE if the URL identifies the protocol; FALSE otherwise.
   */
  private function hasProtocol($url) {
    $parsed_url = parse_url($url);
    if (FALSE === $parsed_url) {
      throw new \InvalidArgumentException(sprintf('The "url" parameter value "%s" is malformed.', $url));
    }
    return isset($parsed_url['scheme']);
  }

  /**
   * Sets the authentication instance.
   *
   * @param AuthenticationInterface $authentication
   *   The authentication mechanism.
   */
  public function setAuthentication(AuthenticationInterface $authentication) {
    $this->authentication = $authentication;
  }

  /**
   * Implements DependencyManagedInterface::getDependencies().
   */
  public function getDependencies() {
    return array(
      'acquia.wip.wiplog' => 'Acquia\Wip\WipLogInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $base = parent::getDescription();
    return sprintf('%s: URL: %s.', $base, $this->url);
  }

  /**
   * {@inheritdoc}
   */
  public function send(SignalInterface $signal) {
    $this->wipLog = WipFactory::getObject('acquia.wip.wiplog');
    try {
      $client = new HttpClient();
      $request_options = array(
        'verify' => WipFactory::getBool('$acquia.wip.ssl.verifyCertificate', TRUE),
        'json' => $signal->convertToObject(),
        'headers' => array(
          'User-Agent' => basename(__FILE__),
        ),
        'allow_redirects' => array(
          'max'       => 5,
          'strict'    => TRUE,
          'referer'   => TRUE,
          'protocols' => array(
            'https',
          ),
        ),
      );
      if (!empty($this->authentication)) {
        // @see http://guzzle.readthedocs.org/en/latest/request-options.html#auth
        $request_options['auth'] = array($this->authentication->getAccountId(), $this->authentication->getSecret());
      }

      $client->post($this->url, $request_options);
      $message = sprintf('Successfully sent URL callback to %s.', $this->url);
      $this->wipLog->log(WipLogLevel::DEBUG, $message, $signal->getObjectId());
    } catch (\Exception $e) {
      $message = sprintf('Failed to send URL callback "%s": %s', $this->url, $e->getMessage());
      $this->wipLog->log(WipLogLevel::ERROR, $message, $signal->getObjectId());
    }
  }

  /**
   * Returns a curl command that can be executed to invoke the callback.
   *
   * @param SignalInterface $signal
   *   The signal to send.
   *
   * @return string
   *   The curl command.
   */
  public function getCurlCommand(SignalInterface $signal) {
    // Determine whether the curl -k option should be used.
    $secure = WipFactory::getBool('$acquia.wip.ssl.verifyCertificate', TRUE);
    if (!empty($this->authentication)) {
      $user = $this->authentication->getAccountId();
      $password = $this->authentication->getSecret();
      $result = sprintf(
        "\\curl -o%s -u %s:%s -X POST --data-binary %s %s",
        !$secure ? 'k' : '',
        escapeshellarg($user),
        escapeshellarg($password),
        escapeshellarg(json_encode($signal->convertToObject())),
        escapeshellarg($this->url)
      );
    } else {
      // No authentication provided.
      $result = sprintf(
        "\\curl -o%s -X POST --data-binary %s %s",
        !$secure ? 'k' : '',
        escapeshellarg(json_encode($signal->convertToObject())),
        escapeshellarg($this->url)
      );
    }
    return $result;
  }

  /**
   * Gets the signal body in JSON format.
   *
   * @param SignalInterface $signal
   *   The signal.
   *
   * @return string
   *   The signal body.
   */
  public function getSignalBodyJson(SignalInterface $signal) {
    return json_encode($signal->convertToObject());
  }

  /**
   * Gets the authentication string associated with this callback.
   *
   * @return string
   *   The authentication string.
   */
  public function getAuth() {
    $result = '';
    if (!empty($this->authentication)) {
      $user = $this->authentication->getAccountId();
      $password = $this->authentication->getSecret();
      $result = sprintf("%s:%s", $user, $password);
    }
    return $result;
  }

  /**
   * Gets the URL associated with this signal.
   *
   * @return string
   *   The URL.
   */
  public function getUrl() {
    return $this->url;
  }

}
