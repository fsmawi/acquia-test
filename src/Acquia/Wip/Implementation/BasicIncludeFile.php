<?php

namespace Acquia\Wip\Implementation;

use Acquia\Wip\IncludeFileInterface;

/**
 * This is a simple implementation of the IncludeFileInterface.
 */
class BasicIncludeFile implements IncludeFileInterface {

  /**
   * The docroot portion of the include file.
   *
   * @var string
   */
  private $docroot = NULL;

  /**
   * The file path portion of the include file.
   *
   * @var string
   */
  private $filePath = NULL;

  /**
   * Initializes this instance.
   *
   * @param string $docroot
   *   The docroot.
   * @param string $file_path
   *   The path to the include file.
   */
  public function __construct($docroot, $file_path) {
    if (!is_string($docroot) || empty($docroot)) {
      throw new \InvalidArgumentException('The $docroot parameter must be a non-empty string.');
    }
    if (!is_string($file_path) || empty($file_path)) {
      throw new \InvalidArgumentException('The $file_path parameter must be a non-empty string.');
    }
    $this->docroot = $docroot;
    $this->filePath = $file_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getDocroot() {
    return $this->docroot;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilePath() {
    return $this->filePath;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullPath() {
    return sprintf('%s%s%s', $this->docroot, DIRECTORY_SEPARATOR, $this->filePath);
  }

}
