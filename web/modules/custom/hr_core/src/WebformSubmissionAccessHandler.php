<?php

namespace Drupal\hr_core;

use \Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use \Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\Access\WebformAccessResult;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Entity\EntityInterface;
use \Drupal\Core\Session\AccountInterface;
use \Drupal\Core\Access\AccessResult;
use \Drupal\Core\Access\AccessResultInterface;

class WebformSubmissionAccessHandler implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Custom Access check.
   */
  public function access(WebformSubmissionInterface $entity, $operation, AccountInterface $account) {
    if (in_array($operation, ['view'])) {

      $webforms = $this->getWebForm();

      if (!in_array($entity->getWebform()->id(), $webforms)) {
        return AccessResult::neutral();
      }

      if ($entity->getWebform()->id() === $webforms[0]) {
        $access = $this->_360_feedback($entity, $operation, $account);
      }
      elseif ($entity->getWebform()->id() === $webforms[1]) {
        $access = $this->_self_review($entity, $operation, $account);
      }
      elseif ($entity->getWebform()->id() === $webforms[2]) {
        $access = $this->_manager_review($entity, $operation, $account);
      }

      if ($access === 'allowed') {
        return \Drupal\Core\Access\AccessResult::allowed();
      }
      elseif ($access === 'neutral') {
        return \Drupal\Core\Access\AccessResult::neutral();
      }
      else {
        return \Drupal\Core\Access\AccessResult::forbidden();
      }
    }
  }


  public function _360_feedback(EntityInterface $entity, $operation, AccountInterface $account) {
    $submission_data = $entity->getData();

    $reviewee = [
      $submission_data['reviewee'],
      $submission_data['primary_reviewer'],
    ];

    // Allow access for owner.
    if ($entity->getOwnerId() === $account->id()) {
      return 'neutral';
    }
    // Allow access for Administrator, HR Admin and Leadership.
    elseif ($account->hasPermission('view any review pages')) {
      return 'allowed';
    }
    // Allow access for Primary manager.
    elseif (in_array($account->id(), $this->getPrimaryManager($reviewee))) {
      return 'neutral';
    }
    // Allow access for Reviewee.
    elseif (in_array($account->id(), $reviewee)) {
      return 'neutral';
    }
    else {
      return 'forbidden';
    }
  }

  public function _self_review(EntityInterface $entity, $operation, AccountInterface $account) {
    // Allow access for owner.
    if ($entity->getOwnerId() === $account->id()) {
      return 'allowed';
    }
    // Allow access for Administrator, HR Admin and Leadership.
    elseif ($account->hasPermission('view any review pages')) {
      return 'allowed';
    }
    // Allow access for Primary and Secondary manager.
    elseif (in_array($account->id(), $this->getPrimaryManager([$entity->getOwnerId()])) ||
      in_array($account->id(), $this->getSecondaryManager([$entity->getOwnerId()]))) {
      return 'neutral';
    }
    else {
      return 'forbidden';
    }
  }

  public function _manager_review(EntityInterface $entity, $operation, AccountInterface $account) {
    $submission_data = $entity->getData();

    $reviewee = [
      $submission_data['name_of_reviewee_select'],
    ];

    // Allow access for owner.
    if ($entity->getOwnerId() === $account->id()) {
      return 'allowed';
    }
    // Allow access for Administrator, HR Admin and Leadership.
    elseif ($account->hasPermission('view any review pages')) {
      return 'allowed';
    }
    // Allow access for Primary and Secondary manager.
    elseif (in_array($account->id(), $this->getPrimaryManager($reviewee)) ||
      in_array($account->id(), $this->getSecondaryManager($reviewee))) {
      return 'neutral';
    }
    // Allow access for Reviewee.
    elseif (in_array($account->id(), $reviewee) && !in_array($entity->getOwnerId(), $this->getSecondaryManager($reviewee))) {
      return 'neutral';
    }
    else {
      return 'forbidden';
    }
  }

  public function getWebForm() {
    $config = \Drupal::config('hr_core.review.settings');
    return [$config->get('feedback'),$config->get('self_review'),$config->get('manager_review')];
  }

  public function getPrimaryManager($reviewee) {
    foreach ($reviewee as $user_id) {
      if ($user_id) {
        $managers = $this->entityTypeManager->getStorage('user')->load($user_id)->get('f_your_line_manager')->referencedEntities();
        $ids = [];

        foreach ($managers as $manager) {
          $ids[] = $manager->id();
        }

        return $ids;
      }
    }

    return NULL;
  }

  public function getSecondaryManager($reviewee) {
    foreach ($reviewee as $user_id) {
      if ($user_id) {
        $managers = $this->entityTypeManager->getStorage('user')->load($user_id)->get('f_secondary_reviewer')->referencedEntities();
        $ids = [];

        foreach ($managers as $manager) {
           $ids[] = $manager->id();
        }

        return $ids;
      }
    }

    return NULL;
  }

}
