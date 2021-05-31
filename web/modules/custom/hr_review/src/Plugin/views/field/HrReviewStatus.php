<?php

namespace Drupal\hr_review\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Annotation\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup views_field_handlers
 *
 * @ViewsField("hr_review_status")
 */
class HrReviewStatus extends FieldPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
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

    $primary_manager_id = $this->getPrimaryManager($values->uid);

    $status = [
      '#type' => 'markup',
      '#markup' => '<span class="">-</span>',
    ];

    if ($primary_manager_id) {
      $manager_review_result = \Drupal::entityTypeManager()
        ->getStorage('webform_submission')
        ->loadByProperties([
          'webform_id' => $webform_id,
          'uid' => $primary_manager_id,
        ]);

      if ($manager_review_result) {
        $review = $this->getReview($manager_review_result, $values->uid);

        if ($review) {
          $review_data = $review->getData();
          $status_name = $review_data['pimary_manager_status_review'];

          if ($status_name) {
            $badge = 'badge-warning';

            if ($status_name === 'completed') {
              $badge = 'badge-success';
              $status_name = 'Completed';
            }
            elseif ($status_name === 'published') {
              $badge = 'badge-info';
              $status_name = 'Ready for meeting';
            }

            $status = [
              '#type' => 'markup',
              '#markup' => '<span class="badge ' . $badge . '">' . $status_name . '</span>',
            ];
          }
        }
      }
    }

    return \Drupal::service('renderer')->renderPlain($status);
  }

  public function getPrimaryManager($user_id) {
    if ($user_id) {
      return $this->entityTypeManager->getStorage('user')
        ->load($user_id)
        ->get('f_your_line_manager')->target_id;
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
