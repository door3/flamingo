<?php

namespace Drupal\hr_review\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Annotation\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup views_field_handlers
 *
 * @ViewsField("hr_status_secondary_review_status")
 */
class HrReviewSecondaryStatus extends FieldPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * @{inheritdoc}
   */
  public function query() {}

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $config = \Drupal::config('hr_core.review.settings');
    $webform_id = $config->get('manager_review');

    $secondary_managers = $this->getSecondaryManager($values->uid);

    foreach ($secondary_managers as $key => $manager) {

      if ($this->currentUser->id() !== $manager->id()) {
        continue;
      }

      $status[$key] = [
        '#type' => 'markup',
        '#markup' => '<span class="">-</span></br>',
      ];

      if ($manager->id()) {
        $manager_review_result = \Drupal::entityTypeManager()
          ->getStorage('webform_submission')
          ->loadByProperties([
            'webform_id' => $webform_id,
            'uid' => $manager->id(),
          ]);

        if ($manager_review_result) {
          $review = $this->getReview($manager_review_result, $values->uid);

          if ($review) {
            if ($review->isDraft()) {
              $status[$key] = [
                '#type' => 'markup',
                '#markup' => '<span class="badge badge-warning">draft</span></br>',
              ];
            }
            else {
              $status[$key] = [
                '#type' => 'markup',
                '#markup' => '<span class="badge badge-success">submitted</span></br>',
              ];
            }
          }
        }
      }
    }

    return \Drupal::service('renderer')->renderPlain($status);
  }

  public function getSecondaryManager($user_id) {
    if ($user_id) {
      $user =  $this->entityTypeManager->getStorage('user')
        ->load($user_id);

      return $user->get('f_secondary_reviewer')->referencedEntities();
    }

    return NULL;
  }

  public function getReview($reviews, $uid) {
    foreach ($reviews as $review) {
      $data = $review->getData();
      if ($data['name_of_reviewee_select'] === $uid) {
        return $review;
      }
    }

    return FALSE;
  }
}
