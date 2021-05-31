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
 * @ViewsField("hr_review_score_practice")
 */
class HrReviewPracticeValues extends HrReviewCoreValues {

  /**
   * The score fields.
   *
   * @var array
   */
  protected $score_fields = [
    'tools_proficiency_score',
    'tools_documentation_quality_score',
    'test_execution_score',
    'communication_score',
    'automated_testing_score',
    'tech_stack_expertise_score',
    'additional_responsibilities_score',
    'growth_score',
    'processes_score',
    'ceremonies_score',
    'deliverables_score',
    'project_concerns_and_risks_score',
    'communication_with_a_client_score',
    'practice_improvements_socre',
    'ba_processes_score',
    'project_ceremonies_score',
    'client_communication_score',
    'data_analysis_and_reporting_skills_score',
    'ba_practice_improvement_score',
    'discovery_research_skills_score',
    'project_definition_score',
    'user_experience_design_skills_score',
    'consulting_skills_score',
  ];

}
