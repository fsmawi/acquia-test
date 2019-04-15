<?php

namespace Acquia\Wip\Exception;

/**
 * Defines an exception type for missing transition blocks in state tables.
 */
class MissingTransitionBlockException extends WipException {

  /**
   * The name of the missing block.
   *
   * @var string
   */
  private $blockName = NULL;

  /**
   * Sets the name of the missing block.
   *
   * @param string $block_name
   *   The block name.
   *
   * @throws \InvalidArgumentException
   *   If the block name is not a string.
   */
  public function setBlock($block_name) {
    if (!is_string($block_name)) {
      throw new \InvalidArgumentException('The $block_name argument must be a string.');
    }
    $this->blockName = $block_name;
  }

  /**
   * Returns the name of the missing block.
   *
   * @return string
   *   The block name.
   */
  public function getBlock() {
    return $this->blockName;
  }

}
