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
 * @ViewsField("days_worked")
 */
class HrDaysWorked extends FieldPluginBase {

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
    $user = $this->entityTypeManager->getStorage('user')->load($values->uid);

    $status = [
      '#type' => 'markup',
      '#markup' => '<span class="text-muted">no</span>',
    ];

    $employee_eligibility = isset($user->f_eligibility) ? $user->f_eligibility->value : 0;

    if ($employee_eligibility == 0 && $user->f_type_of_work->value === 'employee') {
      $today = time();
      $hire_date = $user->f_hire_date->value;
      $new_hire_date = strtotime('+3 month', $hire_date);

      if ($today > $new_hire_date) {
        $status = [
          '#type' => 'markup',
          '#markup' => '<span class="badge badge-success">yes</span>',
        ];
      }
    }

    return \Drupal::service('renderer')->renderPlain($status);
  }

}
