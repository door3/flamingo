<?php

namespace Drupal\hr_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block for User Profile.
 *
 * @Block(
 *   id = "hr_core_user_profile_links",
 *   admin_label = @Translation("User Profile links block"),
 *   category = @Translation("User Profile links block")
 * )
 */
class UserProfileLinks extends BlockBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('current_route_match'),
    );
  }

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $current_user,
    RouteMatchInterface $routeMatch) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $employee = $this->routeMatch->getParameter('user');

    if (is_null($employee) || $employee->id() !== $this->currentUser->id()) {
      return [];
    }

    $build['links'] = [
      '#theme' => 'item_list',
      '#items' => [],
      '#attributes' => ['class' => ['nav d-flex flex-row']],
    ];

    $url_view_profile = Url::fromRoute('entity.user.canonical', ['user' => $this->currentUser->id()]);
    $url_edit_profile = Url::fromRoute('entity.user.edit_form', ['user' => $this->currentUser->id()]);

    $build['links']['#items'][1] = [
      '#type' => 'link',
      '#attributes' => ['class' => ['btn btn-outline-primary mr-3 mb-3 nav-link']],
      '#title' => t('View profile'),
      '#url' => $url_view_profile,
    ];

    $build['links']['#items'][2] = [
      '#type' => 'link',
      '#attributes' => ['class' => ['nav-item btn btn-outline-success mb-3 nav-link']],
      '#title' => t('Edit profile'),
      '#url' => $url_edit_profile,
    ];

    return $build;
  }

}
