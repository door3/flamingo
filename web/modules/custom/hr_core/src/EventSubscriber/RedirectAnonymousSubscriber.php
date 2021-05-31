<?php

namespace Drupal\hr_core\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class RedirectAnonymousSubscriber implements EventSubscriberInterface {

  public function __construct() {
    $this->account = \Drupal::currentUser();
  }

  public function checkAuthStatus(RequestEvent $event) {
    $route_name = \Drupal::routeMatch()->getRouteName();

    if ($this->account->isAnonymous() && !in_array($route_name, ['user.login', 'user.pass', 'user.reset.login', 'user.reset', 'user.reset.form'])) {

      // add logic to check other routes you want available to anonymous users,
      // otherwise, redirect to login page.
      if ($route_name === 'user.login') {
        return;
      }

      $response = new RedirectResponse('/user/login', 301);
      $event->setResponse($response);
    }
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkAuthStatus');
    return $events;
  }

}
