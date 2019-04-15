<?php

namespace Acquia\Wip\Test;

use Acquia\Wip\Implementation\BasicIncludeFile;
use Acquia\Wip\Implementation\IncludeFileInterface;

/**
 * Missing summary.
 */
class IncludeFileInterfaceTest extends \PHPUnit_Framework_TestCase {
  private $docroot = 'docroot';
  private $path = 'path';

  /**
   * Missing summary.
   *
   * @var IncludeFileInterface
   */
  private $includeFile = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->includeFile = new BasicIncludeFile($this->docroot, $this->path);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIncludeFileBadDocroot() {
    new BasicIncludeFile(NULL, $this->path);
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testIncludeFileBadPath() {
    new BasicIncludeFile($this->docroot, NULL);
  }

  /**
   * Missing summary.
   */
  public function testIncludeFileDocroot() {
    $this->assertTrue($this->includeFile->getDocroot() == $this->docroot);
  }

  /**
   * Missing summary.
   */
  public function testIncludeFileFilePath() {
    $this->assertTrue($this->includeFile->getFilePath() == $this->path);
  }

  /**
   * Missing summary.
   */
  public function testIncludeFileGetFullPath() {
    $full_path = sprintf('%s%s%s', $this->docroot, DIRECTORY_SEPARATOR, $this->path);
    $this->assertTrue($this->includeFile->getFullPath() == $full_path);
  }

}
