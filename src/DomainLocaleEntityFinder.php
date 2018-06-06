<?php

namespace Drupal\domain_locale_custom;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class DomainLocaleEntityFinder {

  /**
   * Derive entity data from a given path.
   *
   * @param $path
   *   The drupal path, e.g. /node/2.
   * @param $options array
   *   The options passed to the path processor.
   *
   * @return $entity|NULL
   */
  public function findEntity($path, $options = NULL) {
    $entity = NULL;
    $route = \Drupal::routeMatch()->getRouteObject();

    if(!isset($route)) {
      return $entity;
    }
    $route_path = $route->getPath();
    $type = $this->findEntityType($path);
    $links = $this->findEntityLinks($path);

    if($links == NULL) {
      return $entity;
    }

    // Check that the route pattern is an entity template.
    if (in_array($route_path, $links)) {
      $parts = explode('/', $route_path);
      $i = 0;
      foreach ($parts as $part) {
        if (!empty($part)) {
          $i++;
        }
        if ($part == '{' . $type . '}') {
          break;
        }
      }
      $i++;
      // Get entity path if alias.
      $path_data = explode('/', $path);
      $path_data = array_slice($path_data, 1);
      $path_without_langcode = '/' . implode('/', array_slice($path_data, 1));
      $entity_path = \Drupal::service('path.alias_manager')->getPathByAlias($path_without_langcode);

      // Look! We're using arg() in Drupal 8 because we have to.
      $args = explode('/', $entity_path);
      if (isset($args[$i])) {
        $entity = \Drupal::entityTypeManager()->getStorage($type)->load($args[$i]);
      }
      if(isset($args[$i - 1]) && $args[$i - 1] != 'node') {
        $entity = \Drupal::entityTypeManager()->getStorage($type)->load($args[$i - 1]);
      }

    }

    return $entity;
  }

  /**
   * Get entity links, given an entity type
   *
   * @param $type
   *   The drupal path, e.g. 'taxonomy_term'.
   *
   * @return $entity_links|NULL
   */
  public function getLinksByType($type) {
    $entity_manager = \Drupal::entityTypeManager();
    $entity_type = $entity_manager->getDefinition($type);
    return $entity_type->getLinkTemplates();
  }

  /**
   * Derive entity type links from a given path.
   *
   * @param $path
   *   The drupal path, e.g. /node/2.
   *
   * @return $entity_links|NULL
   */
  public function findEntityLinks($path) {
    $entity_links = NULL;
    $route = \Drupal::routeMatch()->getRouteObject();
    if(!isset($route)) {
      return $entity_links;
    }
    $route_path = $route->getPath();
    $links_node = $this->getLinksByType('node');
    $links_taxonomy = $this->getLinksByType('taxonomy_term');
    if (in_array($route_path, $links_node)) {
      $entity_links = $links_node;
    }
    if (in_array($route_path, $links_taxonomy)) {
      $entity_links = $links_taxonomy;
    }
    return $entity_links;
  }

  /**
   * Derive entity type from a given path.
   *
   * @param $path
   *   The drupal path, e.g. /node/2.
   *
   * @return $entity_type|NULL
   */
  public function findEntityType($path) {
    $entity_type = NULL;
    $route = \Drupal::routeMatch()->getRouteObject();
    if(!isset($route)) {
      return $entity_type;
    }
    $route_path = $route->getPath();
    $links_node = $this->getLinksByType('node');
    $links_taxonomy = $this->getLinksByType('taxonomy_term');
    if (in_array($route_path, $links_node)) {
      $entity_type = 'node';
    }
    if (in_array($route_path, $links_taxonomy)) {
      $entity_type = 'taxonomy_term';
    }
    return $entity_type;
  }

}