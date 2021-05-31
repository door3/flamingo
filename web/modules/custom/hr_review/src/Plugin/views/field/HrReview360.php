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
 * @ViewsField("hr_status_360")
 */
class HrReview360 extends FieldPluginBase {

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
    $webform_id = $config->get('feedback');

    $status = [
      '#type' => 'markup',
      '#markup' => '<span class="">-</span>',
    ];

    $employee_primary_reviewer = $this->getPrimaryManager($values->uid);

    $feeedback_result = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties([
        'webform_id' => $webform_id,
        'uid' => $values->uid,
      ]);

    if ($feeedback_result) {
      $feeedback_result = array_shift($feeedback_result);
      $submission_data = $feeedback_result->getData();
      if (!empty($submission_data["primary_reviewer"]) && $submission_data["primary_reviewer"] == $employee_primary_reviewer) {
        if ($feeedback_result->isDraft()) {
          $status = [
            '#type' => 'markup',
            '#markup' => '<span class="badge badge-warning">draft</span>',
          ];
        }
        else {
          $status = [
            '#type' => 'markup',
            '#markup' => '<span class="badge badge-success">submitted</span>',

          ];
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
}
