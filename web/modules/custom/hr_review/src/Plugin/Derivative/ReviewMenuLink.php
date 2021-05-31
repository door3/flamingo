<?php

namespace Drupal\hr_review\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class that provides the menu links for the Review links.
 */
class ReviewMenuLink extends DeriverBase implements ContainerDeriverInterface {

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
   * Creates a ReviewLinks instance.
   *
   * @param $base_plugin_id
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $routeMatch) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];
    return $links;

    $employee = $this->routeMatch->getParameter('user');

    $links['22'] = [
        'title' => t('Compare Reviews'),
        'route_name' => 'view.compare_reviews.page_compare_reviews',
        'route_parameters' => [
          'user' => $employee->id(),
        ],
      ] + $base_plugin_definition;

    return $links;
  }
}
