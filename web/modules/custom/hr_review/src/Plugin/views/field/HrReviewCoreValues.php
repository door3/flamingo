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
 * @ViewsField("hr_review_score_core_value")
 */
class HrReviewCoreValues extends FieldPluginBase {

  /**
   * The score fields.
   *
   * @var array
   */
  protected $score_fields = [
    'dedicated_client_success_score',
    'succeeding_together_score',
    'get_it_done_score',
    'drive_to_the_why_score',
    'excellence_and_expertise_score',
    'integrity_score'
  ];

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
    $manager_webform_id = $config->get('manager_review');
    $employee_webform_id = $config->get('self_review');
    $uid = $values->uid;

    $managers[] = $this->getPrimaryManager($uid);
    $secondary_managers = $this->getSecondaryManager($uid);

    $result = [];
    $score = 0;
    $num = 0;

    $managers = array_merge($managers, $secondary_managers);

    // Ger score from managers.
    foreach ($managers as $key => $manager) {
      $result[$key] = [
        '#type' => 'markup',
        '#markup' => $manager->getAccountName() . ': -</br>',
      ];

      if ($manager->id()) {
        $manager_review_result = \Drupal::entityTypeManager()
          ->getStorage('webform_submission')
          ->loadByProperties([
            'webform_id' => $manager_webform_id,
            'uid' => $manager->id(),
            'in_draft' => 0
          ]);

        if ($manager_review_result) {
          $review = $this->getReview($manager_review_result, $uid);

          if ($review) {
            $avg_score = $this->calAvgScore($review);
            $avg_score = $avg_score !== 'N/A' ? $avg_score : 0;

            if ($avg_score) {
              $score = $score + $avg_score;
              ++$num;
            }

            $result[$key] = [
              '#type' => 'markup',
              '#markup' => $manager->getAccountName() . ': ' . ($avg_score !== 'N/A' && $avg_score != 0 ? $avg_score : 'N/A') . '</br>',
            ];
          }
        }
      }
    }

    // Calc avg score.
    if ($score > 0 && $num > 0) {
      $manager_avg_score = round($score / $num, 2);

      $result_avg = [
        '#type' => 'markup',
        '#markup' => '<b>Avg. by managers: ' . ($manager_avg_score !== 0 ? $manager_avg_score : 'N/A') . '</b></br>',
      ];
    }
    else {
      $result_avg = [
        '#type' => 'markup',
        '#markup' => 'Avg. by managers: -</br>',
      ];
    }

    $self_review_result = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties([
        'webform_id' => $employee_webform_id,
        'uid' => $uid,
        'in_draft' => 0
      ]);

    if ($self_review_result) {
      $self_review_result = array_shift($self_review_result);
      $avg_score_self = $this->calAvgScore($self_review_result);
      $avg_score_by_managers = ($score > 0 && $num > 0) ? $score / $num : 0;
      if ($avg_score_self !== 'N/A') {
        $defference = $avg_score_self - $avg_score_by_managers;
      }
      else {
        $defference = 0;
      }
//      $class = ($defference > 1 || $defference < -1) ? 'text-danger' : '';
      $class = '';
      $self_result = [
        '#type' => 'markup',
        '#markup' => '<b class="' . $class . '">Self: ' . $avg_score_self . '</b></br>',
      ];
    }
    else {
      $self_result = [
        '#type' => 'markup',
        '#markup' => 'Self: -</br>',
      ];
    }



    array_push($result, $result_avg, $self_result);

    return \Drupal::service('renderer')->renderPlain($result);
  }

  public function calAvgScore($review) {
    $avg_score = 0;
    $sub_data = $review->getData();
    $score_fields = [];

    foreach ($sub_data as $key => $data) {
      if (in_array($key, $this->score_fields)) {
        if (is_numeric($data) && $data > 0) {
          $avg_score = $avg_score + $data;
          $score_fields[] = $key;
        }
      }
    }

    if ($avg_score && count($score_fields)) {
      $avg_score = $avg_score / count($score_fields);
      $avg_score = round($avg_score, 2);
    }

    return $avg_score !== 0 ? $avg_score : 'N/A';
  }

  public function getPrimaryManager($user_id) {
    if ($user_id) {
       $manager_id = $this->entityTypeManager->getStorage('user')
        ->load($user_id)
        ->get('f_your_line_manager')->target_id;

      return $this->entityTypeManager->getStorage('user')
        ->load($manager_id);
    }

    return NULL;
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
