<?php

namespace Drupal\menu_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatch;

/**
 * Default controller for the menu_api module.
 */
class MenuAPIController extends ControllerBase {

    public function build(string $menu = null, int $item = 0) {
        if (!$menu){
            return new JsonResponse(['status' => 'error', 'data' => 'No menu provided.']);
        }
        $menu_tree = \Drupal::menuTree();

        // Menu item
        if ($item == 0) {
            $params = $menu_tree->getCurrentRouteMenuTreeParameters($menu);
            $params->setMaxDepth(1);
        } elseif ($id = $this->getMenuPluginIDByItemID($item)) {
            $params = new \Drupal\Core\Menu\MenuTreeParameters();
            $params->setRoot($id);
            $params->excludeRoot();
        } else {
            return new JsonResponse(['status' => 'error', 'data' => 'Menu item not found.']);
        }

        // Load active trail from given source parameter
        if ($source = \Drupal::request()->query->get('source')) {
            if ($url = \Drupal::service('path.validator')->getUrlIfValid($source)) {
                $route = \Drupal::service('router.route_provider')->getRouteByName($url->getRouteName());
                $route_match = new RouteMatch($url->getRouteName(), $route, $url->getRouteParameters());
                $active_trail = \Drupal::service('menu_api.active_trail');
                $params->setActiveTrail($active_trail->getActiveTrailIds($menu, $route_match));
            }
        }

        $tree = $menu_tree->load($menu, $params);
        if (empty($tree)) {
            return new JsonResponse(['status' => 'error', 'data' => 'Menu not found.']);
        }
        $manipulators = [
            ['callable' => 'menu.default_tree_manipulators:checkAccess'],
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ];
        $trans = $menu_tree->transform($tree, $manipulators);
        $build = $menu_tree->build($trans);
        $html = \Drupal::service('renderer')->render($build);

        return new JsonResponse(['status' => 'ok', 'data' => $html]);
    }

    /**
     * Helper to get menu item Plugin ID from item ID.
     */
    private function getMenuPluginIDByItemID($item) {
        $query = \Drupal::database()->select('menu_link_content', 'm')
            ->fields('m', ['bundle', 'uuid'])
            ->condition('m.id', $item, '=')
            ->execute()->fetchAssoc();
        return !empty($query) ? implode(':', $query) : NULL;
    }

}