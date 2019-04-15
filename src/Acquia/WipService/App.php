<?php

namespace Acquia\WipService;

use Acquia\Wip\Implementation\WipLog;
use Acquia\Wip\WipLogLevel;
use Doctrine\ORM\EntityManagerInterface;
use Silex\Application;

/**
 * Globally-accessible container for the application.
 */
class App {
  /**
   * The application instance.
   *
   * @var Application
   */
  protected static $app = NULL;

  /**
   * Sets the application instance.
   *
   * @param Application $app
   *   The application instance.
   */
  public static function setApp(Application $app) {
    static::$app = $app;
  }

  /**
   * Gets the application instance.
   *
   * @return Application
   *   The application instance.
   */
  public static function getApp() {
    return static::$app;
  }

  /**
   * Gets the entity manager.
   *
   * @return EntityManagerInterface
   *   The entity manager.
   *
   * @throws \Exception
   *   If the entity manager fails to connect to the database.
   */
  public static function getEntityManager() {
    static $verified = FALSE;
    /** @var EntityManagerInterface $result */
    $result = static::$app['orm.em'];
    if (!$verified) {
      $max_attempts = 3;
      $attempts = 0;
      $connected = FALSE;
      $exception = NULL;
      $start_time = microtime(TRUE);
      do {
        try {
          $attempts++;
          if (!$result->getConnection()->isConnected()) {
            $result->getConnection()->connect();
          }
          $result->getConnection()->query('SELECT 1 FROM state');
          $connected = TRUE;
        } catch (\Exception $e) {
          $exception = $e;
          $result->getConnection()->close();
          sleep(1);
        }
      } while (!$connected && $attempts < $max_attempts);
      $duration = microtime(TRUE) - $start_time;
      if ($connected && $attempts > 1) {
        WipLog::getWipLog()->log(
          WipLogLevel::WARN,
          sprintf(
            'Connected to the database after %d attempts and %0.3f seconds.',
            $attempts,
            $duration
          )
        );
      }
      if (!$connected && !empty($exception)) {
        WipLog::getWipLog()->log(
          WipLogLevel::FATAL,
          sprintf(
            'Failed to connect to the database after %d attempts and %0.3f seconds',
            $attempts,
            $duration
          )
        );
        // Throw the exception to get the normal handling behavior.
        throw $exception;
      }
      $verified = TRUE;
    }
    return $result;
  }

}
