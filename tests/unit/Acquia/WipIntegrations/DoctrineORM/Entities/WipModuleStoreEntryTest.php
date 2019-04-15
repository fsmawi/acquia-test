<?php

namespace Acquia\WipIntegrations\DoctrineORM\Entities;

use Acquia\WipIntegrations\DoctrineORM\Entities\WipModuleStoreEntry;
use Acquia\Wip\WipModule;
use Acquia\Wip\WipModuleInterface;

/**
 * Tests the WipModuleStoreEntry methods.
 */
class WipModuleStoreEntryTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests that toWipModule converts from an entry exactly.
   */
  public function testToWipModule() {
    $entry = new WipModuleStoreEntry();
    $entry->setName('name');
    $entry->setVersion('0.1');
    $entry->setVcsUri('uri');
    $entry->setVcsPath('path');
    $entry->setDirectory('directory');
    $includes = ['autoload.php'];
    $entry->setIncludes(serialize($includes));
    $entry->setEnabled(1);
    $entry->setReady(0);

    /** @var WipModuleInterface $module */
    $module = $entry->toWipModule();

    $this->assertEquals($module->getName(), $entry->getName());
    $this->assertEquals($module->getVersion(), $entry->getVersion());
    $this->assertEquals($module->getVcsUri(), $entry->getVcsUri());
    $this->assertEquals($module->getVcsPath(), $entry->getVcsPath());
    $this->assertEquals($module->getDirectory(), $entry->getDirectory());
    $this->assertEquals($module->getIncludes(), $includes);
    $this->assertEquals($module->isEnabled(), $entry->getEnabled());
    $this->assertEquals($module->isReady(), $entry->getReady());
  }

  /**
   * Tests that fromWipModule converts to an entry exactly.
   */
  public function testFromWipModule() {
    $module = new WipModule();
    $module->setName('name');
    $module->setVersion('0.1');
    $module->setVcsUri('uri');
    $module->setVcsPath('path');
    $module->setDirectory('directory');
    $module->setIncludes(['autoload.php']);
    $module->enable();
    $module->setReady(FALSE);

    $entry = WipModuleStoreEntry::fromWipModule($module);

    $this->assertEquals($entry->getName(), $module->getName());
    $this->assertEquals($entry->getVersion(), $module->getVersion());
    $this->assertEquals($entry->getVcsUri(), $module->getVcsUri());
    $this->assertEquals($entry->getVcsPath(), $module->getVcsPath());
    $this->assertEquals($entry->getDirectory(), $module->getDirectory());
    $this->assertEquals($entry->getIncludes(), serialize($module->getIncludes()));
    $this->assertEquals($entry->getEnabled(), $module->isEnabled());
    $this->assertEquals($entry->getReady(), $module->isReady());
  }

}
