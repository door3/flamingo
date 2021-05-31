<?php

namespace Drupal\hr_core\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReviewSettingsForm  extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'review_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hr_core.review.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('hr_core.review.settings');

    $form['config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Review Setting'),
      '#tree' => TRUE,
    ];

    $options = $this->getWebForms();

    $form['config']['feedback'] = [
      '#title' => $this->t('360 Feedback Form'),
      '#type' => 'select',
      '#options' => $options['360 Feedback'],
      '#required' => TRUE,
      '#default_value' => $config->get('feedback') ? $config->get('feedback') : '_none',
    ];

    $form['config']['self_review'] = [
      '#title' => $this->t('Self Review Form'),
      '#type' => 'select',
      '#options' => $options['Self Review'],
      '#required' => TRUE,
      '#default_value' => $config->get('self_review') ? $config->get('self_review') : '_none',
    ];

    $form['config']['manager_review'] = [
      '#title' => $this->t('Manager Review Form'),
      '#type' => 'select',
      '#options' => $options['Manager Review'],
      '#required' => TRUE,
      '#default_value' => $config->get('manager_review') ? $config->get('manager_review') : '_none',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hr_core.review.settings');
    $values = $form_state->getValues();

    foreach ($values['config'] as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get WebForms
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getWebForms() {
    // Get Webform storage.
    $webform_storage = $this->entityTypeManager->getStorage('webform');

    return $webform_storage->getOptions(FALSE);
  }

}
