<?php

namespace Acquia\Wip;

/**
 * Methods for objects providing a visualization of a state graph.
 */
interface VisualizationInterface {

  /**
   * Sets the WIP object to visualize.
   *
   * @param string $state_table
   *   The state table.
   */
  public function setStateTable($state_table);

  /**
   * Generates a visual representation of the state machine graph.
   *
   * @param string $filename
   *   If provided, the image will be written to this file, otherwise written
   *   directly to stdout.
   */
  public function visualize($filename = NULL);

}
