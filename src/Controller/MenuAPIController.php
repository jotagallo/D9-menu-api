<?php

namespace Drupal\menu_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;

/**
 * Default controller for the menu_api module.
 */
class MenuAPIController extends ControllerBase {

    public function build(string $menu = null, int $item = 0) {
        if (!$menu){
            $response = ['status' => 'error', 'data' => 'No menu provided.'];
            return new JsonResponse($response);
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
            $response = ['status' => 'error', 'data' => 'Menu item not found.'];
            return new JsonResponse($response);
        }

        // Load active trail from given source parameter
        if ($source = \Drupal::request()->query->get('source')) {
            if (\Drupal::service('path.validator')->getUrlIfValid($source)) {
                // @TODO We have to extend the active_trail service from core
                $active_trail = \Drupal::service('menu.active_trail');
                // dump($active_trail->getActiveTrailIds($menu));die;
            }
        }

        $tree = $menu_tree->load($menu, $params);
        if (empty($tree)) {
            $response = ['status' => 'error', 'data' => 'Menu not found.'];
            return new JsonResponse($response);
        }
        $manipulators = [
            ['callable' => 'menu.default_tree_manipulators:checkAccess'],
            ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
        ];
        $trans = $menu_tree->transform($tree, $manipulators);
        $build = $menu_tree->build($trans);
        $html = \Drupal::service('renderer')->render($build);

        $response = ['status' => 'ok', 'data' => $html];
        return new JsonResponse($response);
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