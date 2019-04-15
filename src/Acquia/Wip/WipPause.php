<?php

namespace Acquia\Wip;

/**
 * Container for various constants related to pausing WIP tasks.
 */
class WipPause {

  /**
   * No pause is in effect.
   */
  const NONE = 0;

  /**
   * A task has been paused individually.
   */
  const TASK = 1;

  /**
   * An entire task group has been paused.
   *
   * This means all tasks with the same group name.
   */
  const GROUP = 2;

  /**
   * An entire task family has been paused.
   *
   * This is typically a task and all of its descendant tasks.
   */
  const FAMILY = 4;

  /**
   * All task processing is paused.
   */
  const ALL = 8;
}
