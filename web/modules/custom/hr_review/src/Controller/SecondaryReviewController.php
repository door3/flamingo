<?php

namespace Drupal\hr_review\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SecondaryReviewController.
 */
class SecondaryReviewController extends ControllerBase  {

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $routeMatch, AccountInterface $current_user, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $routeMatch;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('request_stack'),
    );
  }

  /**
   * Returns a render-able array for a review page.
   */
  public function content() {
    $build = [];

    $build['links'] = [
      '#theme' => 'item_list',
      '#items' => [],
      '#attributes' => ['class' => ['nav d-flex flex-row']],
    ];

    $previous_page = $this->referer();

    $employee = $this->routeMatch->getParameter('user');

    $url_view_compare = Url::fromRoute('view.compare_reviews.page_compare_reviews',[], ['query' => ['employee' => $employee->id()]]);
    $url_webform_review = Url::fromRoute('entity.webform.canonical', ['webform' => '2020_annual_performance_manager'], ['query' => ['employee' => $employee->id(), 'department' => $employee->f_department->value, 'manager' => 'secondary']]);

    if ($this->currentUser->hasPermission('view compare reviews page')) {
      $build['links']['#items'][1] = [
        '#type' => 'link',
        '#attributes' => ['class' => ['btn btn-outline-primary mr-3 mb-3 nav-link']],
        '#title' => t('Compare Reviews'),
        '#url' => $url_view_compare,
      ];
    }

    $build['links']['#items'][2] = [
      '#type' => 'link',
      '#attributes' => ['class' => ['nav-item btn btn-outline-success mb-3 nav-link']],
      '#title' => t('Add Manager review'),
      '#url' => $url_webform_review,
    ];

    $build['user_information'] = [
      '#markup' => '',
    ];

    return $build;
  }

  public function referer() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return $request->headers->get('referer');
    }
    // If this is called prior to the request being pushed to the stack fallback
    // to built-in globals (if available) or the system time.
    return '';
  }

}
