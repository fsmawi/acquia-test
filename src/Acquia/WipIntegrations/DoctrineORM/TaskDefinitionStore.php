<?php

namespace Acquia\WipIntegrations\DoctrineORM;

use Acquia\WipIntegrations\DoctrineORM\Entities\TaskDefinitionEntry;
use Acquia\WipInterface\TaskDefinitionStoreInterface;
use Acquia\WipService\App;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Provides CRUD functions for ECS task definitions.
 */
class TaskDefinitionStore implements TaskDefinitionStoreInterface {

  /**
   * The entity name.
   */
  const ENTITY_NAME = 'Acquia\WipIntegrations\DoctrineORM\Entities\TaskDefinitionEntry';

  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new instance of TaskDefinitionStore.
   */
  public function __construct() {
    $this->entityManager = App::getEntityManager();
  }

  /**
   * {@inheritdoc}
   */
  public function get($name, $region, $revision = NULL) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $keys = array(
      'name' => $name,
      'region' => $region,
    );
    if (isset($revision)) {
      $keys['revision'] = $revision;
    } else {
      $rsm = new ResultSetMapping();
      $rsm->addScalarResult('revision', 'revision');
      /** @var NativeQuery $query */
      $query = $this->entityManager
        ->createNativeQuery(
          'SELECT MAX(revision) AS revision FROM task_definition WHERE name = :name AND region = :region',
          $rsm
        );

      $query->setParameters(array(
        ':name' => $name,
        ':region' => $region,
      ));
      $keys['revision'] = $query->getSingleScalarResult();
    }

    // If we get here and still have no revision, we cannot proceed.
    if (empty($keys['revision'])) {
      return NULL;
    }

    /** @var TaskDefinitionEntry $value */
    $value = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findOneBy($keys);

    if ($value) {
      $result = unserialize($value->getDefinition());
      $result['revision'] = $value->getRevision();
      return $result;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function save($name, $region, $definition, $revision) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $task_definition = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findOneBy(array(
        'name' => $name,
        'region' => $region,
        'revision' => $revision,
      ));
    if (!$task_definition) {
      $task_definition = new TaskDefinitionEntry();
      $task_definition->setName($name);
      $task_definition->setRegion($region);
    }
    $task_definition->setDefinition(serialize($definition));
    $task_definition->setRevision($revision);

    $this->entityManager->persist($task_definition);
    $this->entityManager->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name, $region, $revision) {
    // Ensure that ORM's static cache is not interfering.
    $this->entityManager->clear();

    $task_definition = $this->entityManager
      ->getRepository(self::ENTITY_NAME)
      ->findOneBy(array(
        'name' => $name,
        'region' => $region,
        'revision' => $revision,
      ));
    if ($task_definition) {
      $this->entityManager->remove($task_definition);
      $this->entityManager->flush();
    }
  }

}
