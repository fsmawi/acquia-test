<?php

namespace Acquia\Wip;

/**
 * The IncludeFile interface represents a PHP file required for deserialization.
 *
 * This interface is used as a return type in @link Wip::getIncludes @endlink.
 */
interface IncludeFileInterface {

  /**
   * Creates a new instance of IncludeFile.
   *
   * This IncludeFile instance will represent the file at /$docroot/$path,
   *
   * @param string $docroot
   *   The absolute path to the container docroot.
   * @param string $file_path
   *   The relative path from the container docroot to the file.
   *
   * @throws \InvalidArgumentException
   *   If the docroot or file_path are empty or has a type other than string.
   */
  public function __construct($docroot, $file_path);

  /**
   * Returns the docroot portion of the include file.
   *
   * The docroot portion is the absolute path to the container's docroot.
   *
   * @return string
   *   The docroot path.
   */
  public function getDocroot();

  /**
   * Returns the file path portion of the include file.
   *
   * The file path is the relative path starting from the container's docroot to
   * the include file.
   *
   * @return string
   *   The file path.
   */
  public function getFilePath();

  /**
   * Gets the full path to the include file.
   *
   * @return string
   *   The full path, suitable for using with include_once.
   */
  public function getFullPath();

}
