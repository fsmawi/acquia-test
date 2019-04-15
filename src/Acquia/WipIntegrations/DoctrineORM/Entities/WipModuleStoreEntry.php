<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\Wip\WipModule;
use Acquia\Wip\WipModuleInterface;

// @codingStandardsIgnoreStart
/**
 * Defines an entity for storing a module.
 *
 * @Entity @Table(name="wip_module", options={"engine"="InnoDB"}, indexes={
 *   @Index(name="enabled_idx", columns={"enabled"}),
 *   @Index(name="ready_idx", columns={"ready"})
 * })
 */
class WipModuleStoreEntry {

  // @codingStandardsIgnoreEnd
  /**
   * The unique name of the record.
   *
   * @var string
   *
   * @Id @Column(type="string", length=255, unique=true)
   */
  private $name;

  /**
   * The version of the module.
   *
   * @var string
   *
   * @Column(type="string", length=255)
   */
  private $version;

  /**
   * The VCS URI to the module source.
   *
   * @var string
   *
   * @Column(name="vcs_uri", type="text")
   */
  private $vcsUri;

  /**
   * The VCS path for this version of the module.
   *
   * @var string
   *
   * @Column(name="vcs_path", type="string")
   */
  private $vcsPath;

  /**
   * The module directory.
   *
   * @var string
   *
   * @Column(type="string", length=255)
   */
  private $directory;

  /**
   * The list of files to include.
   *
   * @var string[]
   *
   * @Column(type="text")
   */
  private $includes;

  /**
   * Whether the module is enabled.
   *
   * @var bool
   *
   * @Column(type="integer", options={"unsigned":true})
   */
  private $enabled;

  /**
   * Whether the module is ready.
   *
   * @var bool
   *
   * @Column(type="integer", options={"unsigned":true})
   */
  private $ready;

  /**
   * Gets the unique name.
   *
   * @return string
   *   The unique name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the unique name.
   *
   * @param string $name
   *   The unique name.
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Gets the version.
   *
   * @return string
   *   The version, in any format.
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * Sets the version.
   *
   * @param string $version
   *   The version, in any format.
   */
  public function setVersion($version) {
    $this->version = $version;
  }

  /**
   * Gets the VCS URI.
   *
   * @return string
   *   The VCS URI to the module source.
   */
  public function getVcsUri() {
    return $this->vcsUri;
  }

  /**
   * Sets the VCS URI.
   *
   * @param string $vcs_uri
   *   The VCS URI to the module source.
   */
  public function setVcsUri($vcs_uri) {
    $this->vcsUri = $vcs_uri;
  }

  /**
   * Gets the VCS path.
   *
   * @return string
   *   The VCS path to the module source.
   */
  public function getVcsPath() {
    return $this->vcsPath;
  }

  /**
   * Sets the VCS path.
   *
   * @param string $vcs_path
   *   The VCS path in the module source.
   */
  public function setVcsPath($vcs_path) {
    $this->vcsPath = $vcs_path;
  }

  /**
   * Gets the directory.
   *
   * @return string
   *   The name of the directory containing all module files.
   */
  public function getDirectory() {
    return $this->directory;
  }

  /**
   * Sets the directory.
   *
   * @param string $directory
   *   The name of the directory containing all module files.
   */
  public function setDirectory($directory) {
    $this->directory = $directory;
  }

  /**
   * Gets the includes.
   *
   * @return string[]
   *   The names of the files to PHP require.
   */
  public function getIncludes() {
    return unserialize($this->includes);
  }

  /**
   * Sets the includes.
   *
   * @param string $includes
   *   The names of the files to PHP require.
   */
  public function setIncludes($includes) {
    $this->includes = serialize($includes);
  }

  /**
   * Gets whether the module is enabled.
   *
   * @return bool
   *   The enabled status of the module.
   */
  public function getEnabled() {
    return boolval($this->enabled);
  }

  /**
   * Sets whether the module is enabled.
   *
   * @param bool $enabled
   *   The enabled status of the module.
   */
  public function setEnabled($enabled) {
    $this->enabled = intval($enabled);
  }

  /**
   * Gets whether the module is ready.
   *
   * @return bool $ready
   *   The ready status of the module.
   */
  public function getReady() {
    return boolval($this->ready);
  }

  /**
   * Sets whether the module is ready.
   *
   * @param bool $ready
   *   The ready status of the module.
   */
  public function setReady($ready) {
    $this->ready = intval($ready);
  }

  /**
   * Converts the given module to an entry.
   *
   * @param WipModuleInterface $wip_module
   *   A module to be converted.
   *
   * @return WipModuleStoreEntry
   *   The result of conversion to entry.
   */
  public static function fromWipModule(WipModuleInterface $wip_module) {
    $entry = new WipModuleStoreEntry();

    $entry->setName($wip_module->getName());
    $entry->setVersion($wip_module->getVersion());
    $entry->setVcsUri($wip_module->getVcsUri());
    $entry->setVcsPath($wip_module->getVcsPath());
    $entry->setDirectory($wip_module->getDirectory());
    $entry->setIncludes(serialize($wip_module->getIncludes()));
    $entry->setEnabled($wip_module->isEnabled());
    $entry->setReady($wip_module->isReady());

    return $entry;
  }

  /**
   * Converts an entry to a module.
   *
   * @return WipModuleInterface
   *   The resulting module from a converted entry.
   */
  public function toWipModule() {
    $wip_module = new WipModule();

    $wip_module->setName($this->getName());
    $wip_module->setVersion($this->getVersion());
    $wip_module->setVcsUri($this->getVcsUri());
    $wip_module->setVcsPath($this->getVcsPath());
    $wip_module->setDirectory($this->getDirectory());
    $wip_module->setIncludes(unserialize($this->getIncludes()));
    if ($this->getEnabled()) {
      $wip_module->enable();
    } else {
      $wip_module->disable();
    }
    $wip_module->setReady($this->getReady());

    return $wip_module;
  }

}
