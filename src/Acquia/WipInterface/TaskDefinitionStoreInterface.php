<?php

namespace Acquia\WipInterface;

/**
 * Interface for storage implementations for task definitions.
 *
 * A task definition is a concept that is required by Amazon ECS. A task must
 * be defined before a container can be launched to run it. In a sense, a "task"
 * in the Amazon sense really is a "container". This interface does not exist in
 * wipng, as wipng would not normally need to know implementation details such
 * as ECS.
 */
interface TaskDefinitionStoreInterface {

  /**
   * Loads a task definition by name and region.
   *
   * @param string $name
   *   The name of the task defintion corresponding to a given task (@see
   *   EcsContainer::getEcsTaskFamilyName).
   * @param string $region
   *   The AWS region for which the task is registered.
   * @param mixed $revision
   *   (Optional). If supplied, the specific revision will be retrieved,
   *   otherwise, the latest revision will be returned.
   *
   * @return mixed
   *   Either an array of task definition data, if found, or NULL if none exists
   *   in the database.
   */
  public function get($name, $region, $revision = NULL);

  /**
   * Inserts or updates a task definition entry for a given task and region.
   *
   * @param string $name
   *   The name of the task defintion corresponding to a given task (@see
   *   EcsContainer::getEcsTaskFamilyName).
   * @param string $region
   *   The AWS region for which the task is to be registered.
   * @param array $definition
   *   An array of information defining the task.
   * @param int $revision
   *   The revision number of this task definition (returned by the ECS API).
   */
  public function save($name, $region, $definition, $revision);

  /**
   * Removes a task definition entry.
   *
   * @param string $name
   *   The name of the task defintion corresponding to a given task (@see
   *   EcsContainer::getEcsTaskFamilyName).
   * @param string $region
   *   The AWS region for which the task is registered.
   * @param int $revision
   *   The revision number that is to be deleted.
   */
  public function delete($name, $region, $revision);

}
