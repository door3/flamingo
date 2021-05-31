<?php

namespace Drupal\hr_review\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReviewAccess implements ContainerInjectionInterface {

  /**
   * @var EntityTypeManagerInterface $entityTypeManager.
   */
  protected $entityTypeManager;

  /**
   * RouteMatch instance.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * User instance.
   */
  protected static $employee;

  /**
   * User id.
   */
  protected static $primary_manager = [];

  /**
   * User id.
   */
  protected static $secondary_manager = [];

  /**
   * Creates constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $routeMatch) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $routeMatch;
    self::$employee = $this->routeMatch->getParameter('user');

    $primary_managers = $this->routeMatch->getParameter('user')->get('f_your_line_manager')->referencedEntities();
    foreach ($primary_managers as $manager) {
      self::$primary_manager[] = $manager->id();
    }

    $secondary_managers = $this->routeMatch->getParameter('user')->get('f_secondary_reviewer')->referencedEntities();
    foreach ($secondary_managers as $manager) {
      self::$secondary_manager[] = $manager->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * Check whether the user has permission.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function access(AccountInterface $account) {
    if ($account->hasPermission('view any review pages')) {
      return AccessResult::allowed();
    }
    elseif (in_array($account->id(), self::$primary_manager)) {
      return AccessResult::allowed();
    }
    elseif (in_array($account->id(), self::$secondary_manager)) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }
}
