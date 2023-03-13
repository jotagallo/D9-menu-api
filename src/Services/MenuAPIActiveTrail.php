<?php

namespace Drupal\menu_api\Services;

use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Custom Service implementation of the active menu trail service.
 */
class MenuAPIActiveTrail extends MenuActiveTrail {

  /**
   * {@inheritdoc}
   *
   * Override getActiveTrailIds() method to set $route_match parameter from anywhere.
   * This can build a menu tree with active trails context from a given URL and not only the current path.
   */
  public function getActiveTrailIds($menu_name, RouteMatchInterface $route_match = null) {
    if ($route_match) {
      $this->routeMatch = $route_match;
    }
    return $this->get($menu_name);
  }

}