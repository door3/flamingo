<?php

namespace Drupal\hr_review;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SelfReviewNotificationCron class.
 */
class SelfReviewNotificationCron implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * UnsubscribeController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('review_reminder')
    );
  }

  /**
   * Subscription check cron process.
   */
  public function job() {
    $config = \Drupal::config('hr_core.review.settings');
    $webform_id = $config->get('self_review');

    $employees = $this->getAllEmployees();

    if (!$employees) {
      return;
    }

    foreach ($employees as $employee) {

      $self_review_result = \Drupal::entityTypeManager()
        ->getStorage('webform_submission')
        ->loadByProperties([
          'webform_id' => $webform_id,
          'uid' => $employee->id(),
        ]);

      if ($self_review_result) {
        $self_review_result = array_shift($self_review_result);
        if ($self_review_result->isDraft()) {
          $this->sendReminder($employee, 'draft');
        }
      }
      else {
        $this->sendReminder($employee);
      }
    }
  }

  /**
   * Helper function.
   * Send email to employee.
   */
  protected function getAllEmployees() {
    $employees = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(['status' => TRUE]);

    foreach ($employees as $key => $employee) {

      if (!$this->eligibility($employee)) {
        unset($employees[$key]);
      }
    }

    return $employees;
  }

  /**
   * Helper function.
   * Check user eligibility.
   */
  protected function eligibility($employee) {
    $employee_eligibility = isset($employee->f_eligibility) ? $employee->f_eligibility->value : 0;

    if ($employee_eligibility == 0 && $employee->f_type_of_work->value === 'employee') {
      $today = time();
      $hire_date = $employee->f_hire_date->value;
      $new_hire_date = strtotime('+3 month', $hire_date);

      if ($today > $new_hire_date) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Helper function.
   * Send email to employee.
   */
  protected function sendReminder($employee, bool $draft = FALSE) {
    // TODO: Use it for testing.
    // $employee->setEmail('ivan.berezhnov+test' . $employee->id() . '@door3.com')->save();

    $message_logger = 'Review Reminder: email sent to ' . $employee->getAccountName();
    $this->logger->debug($message_logger);

    _user_mail_notify('self_review_reminder', $employee);
  }

}
