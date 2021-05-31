<?php

namespace Drupal\hr_core;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Entity\EntityInterface;
use \Drupal\Core\Session\AccountInterface;
use \Drupal\Core\Access\AccessResult;
use \Drupal\Core\Access\AccessResultInterface;

class UserAccessHandler implements ContainerInjectionInterface {

  /**
   * Constructor.
   */
  public function __construct() {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Custom Access check.
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account) {
    if (in_array($operation, ['view', 'edit', 'update', 'create'])) {
      // Allow access for Administrator, HR Admin and Leadership.
      if ($account->hasPermission('view any review pages')) {
        return AccessResult::allowed();
      }

      // Allow access for self page.
      if ($entity->id() === $account->id()) {
        return AccessResult::neutral();
      }

      $primary_managers = $entity->get('f_your_line_manager')->referencedEntities();
      $primary_ids = [];

      foreach ($primary_managers as $manager) {
        $primary_ids[] = $manager->id();
      }

      // Allow access for Primary manager.
      if ($entity->hasField('f_your_line_manager') &&
        in_array($account->id(), $primary_ids)) {
        return AccessResult::neutral();
      }

      $secondary_managers = $entity->get('f_secondary_reviewer')->referencedEntities();
      $secondary_ids = [];

      foreach ($secondary_managers as $manager) {
        $secondary_ids[] = $manager->id();
      }

      // Allow access for Secondary manager.
      if ($entity->hasField('f_secondary_reviewer') &&
         in_array($account->id(), $secondary_ids)) {
        return AccessResult::neutral();
      }

      return AccessResult::forbidden();
    }
  }
}
