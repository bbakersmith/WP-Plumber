<?php

require_once(dirname(__FILE__).'/PlumberRoute.php');
require_once(dirname(__FILE__).'/PlumberRouteCallback.php');

class Plumber {


  public static function initialize_routes($args) {
    if(array_key_exists('routes', $args)) {

      if(array_key_exists('route_templates', $args)) {
        $routes = self::apply_all_route_templates(
          $args['routes'],
          $args['route_templates']
        );
      } else {
        $routes = $args['routes'];
      }

      $rank = 0;
      foreach($routes as $route => $definition) {
        $definition['id'] = $rank;
        $definition['path'] = $route;
        $GLOBALS['wp_plumber_routes'][$rank] = new PlumberRoute($definition);
        $rank++;
      }
    }
  }


  public static function get_route_definitions($router) {
    $wp_router_definitions = self::generate_route_definitions();
    foreach($wp_router_definitions as $route => $definition) {
      $router->add_route($route, $definition);
    }
  }


  // should be private, but will need to mock get_route_definitions
  public static function generate_route_definitions() {
    $all_definitions = array();
    foreach($GLOBALS['wp_plumber_routes'] as $route) {
      foreach($route->router_definition as $id => $definition) {
        $all_definitions[$id] = $definition;
      }
    }
    return $all_definitions;
  }


  private static function apply_all_route_templates($routes, $templates) {
    $all_applied_routes = array();

    // merge to generate initial arg set
    foreach($routes as $route => $definition) {
      if(array_key_exists('route_template', $definition)) {
        $new_definition = self::apply_route_template($definition, $templates);
      } else {
        $new_definition = $definition;
      }
      $all_applied_routes[$route] = $new_definition;
    }
    return $all_applied_routes;
  }


  private static function apply_route_template($definition, $templates) {
    if(array_key_exists($definition['route_template'], $templates)) {
      // start from the template
      $base_definition = $templates[$definition['route_template']];
      unset($definition['route_template']);

      // add pods
      if(array_key_exists('pods', $definition)) {
        if(array_key_exists('pods', $base_definition)) {
          $base_definition['pods'] = array_unique(array_merge(
            $base_definition['pods'], 
            $definition['pods']
          ));
        } else {
          $base_definition['pods'] = $definition['pods'];
        }
        unset($definition['pods']);
      }

      $merged_definition = array_merge($base_definition, $definition);

      if(array_key_exists('route_template', $merged_definition)) {
        return self::apply_route_template($merged_definition, $templates);
      } else {
        return $merged_definition;
      }
    }
  }


  // TODO support for postprocessor function

}

?>
