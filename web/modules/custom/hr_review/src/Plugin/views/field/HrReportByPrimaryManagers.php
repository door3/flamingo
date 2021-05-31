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
 * @ViewsField("hr_report_by_primary_managers")
 */
class HrReportByPrimaryManagers extends FieldPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  protected $manager_type = 'f_your_line_manager';

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
    $manager = $values->_entity;

    $get_direct_reports = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->condition('status', 1)
      ->condition($this->manager_type, $manager->id());

    $results = $get_direct_reports->execute();

    $manager_review = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties([
        'webform_id' => $webform_id,
        'uid' => $manager->id(),
      ]);

    foreach ($results as $key => $employee_id) {
      $employee = $this->entityTypeManager->getStorage('user')->load($employee_id);

      $status[$key] = [
        '#type' => 'markup',
        '#markup' => $employee->getAccountName() . ': <span class="">-</span></br>',
      ];

      if ($manager_review) {
        $review = $this->getReview($manager_review, $employee->id());

        if ($review) {
          if ($review->isDraft()) {
            $status[$key] = [
              '#type' => 'markup',
              '#markup' => $employee->getAccountName() . ': <span class="badge badge-warning">draft</span></br>',
            ];
          }
          else {
            $status[$key] = [
              '#type' => 'markup',
              '#markup' => $employee->getAccountName() . ': <span class="badge badge-success">submitted</span></br>',
            ];
          }
        }
      }
    }

    return \Drupal::service('renderer')->renderPlain($status);
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
