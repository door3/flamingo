<?php

namespace Drupal\hr_review\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ReviewReminderForm
 *
 * @internal
 */
class ReviewReminderForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
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
   * HeaderForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('review_reminder')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'review_reminder';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['review_reminder.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $employees = $this->entityTypeManager->getStorage('user')->loadByProperties([
      'status' => 1
    ]);

    $list_employees = [];

    foreach ($employees as $uid => $employee) {
      if ($uid == 1) {
        continue;
      }

      $list_employees[$uid] = $employee->getDisplayName();
    }

    natsort($list_employees);

    $form['employees'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Employees'),
      '#options' => $list_employees,
      '#multiple' => TRUE,
    ];

    $form['reminder'] = [
      '#title' => $this->t('Reminder Type'),
      '#type' => 'select',
      '#options' => [
        'self_review_reminder' => 'Self Review',
        'primary_reviewer_reminder' => 'Primary reviewers',
        'secondary_reviewer_reminder' => 'Secondary reviewers'
      ],
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $employees = $form_state->getUserInput()['employees'];
    $reminder = $form_state->getUserInput()['reminder'];

    foreach ($employees as $uid => $employee) {

      if (is_null($employee)) {
        continue;
      }

      $employee = $this->entityTypeManager->getStorage('user')->load($uid);

      $message_logger = 'Review Reminder: email sent to ' . $employee->getAccountName();
      $this->logger->debug($message_logger);

      _user_mail_notify($reminder, $employee);
    }

    parent::submitForm($form, $form_state);
  }

}
