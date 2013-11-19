<?php

class Plumber {


  public static function register_routes($args) {
// print time();
// print "REGISTER";
    PlumberRouteFactory::create_routes($args);
  }


  public static function callback() {
// print time();
// print "CALLBACK";
    // first callback arg is id, the rest are query_vars
    $args = func_get_args();
    $id = $args[0];
    $page_arguments = array_slice($args, 1);
// print $page_arguments;
    self::get_route_by_id($id)->callback($page_arguments);
  }


  public static function create_router_definitions($router) {
    $wp_router_definitions = self::generate_route_definitions();
// print time();
// print_r($wp_router_definitions);
    foreach($wp_router_definitions as $route => $definition) {
      $router->add_route($definition['path'], $definition);
    }
// print time();
// print_r($router);
  }


  // should be private, but will need to mock create_router_definitions
  public static function generate_route_definitions() {
    $all_definitions = array();
    foreach($GLOBALS['wp_plumber_routes'] as $route) {
      $all_definitions[$route->id] = $route->router_definition;
    }
    return $all_definitions;
  }


  private static function get_route_by_id($id) {
    return $GLOBALS['wp_plumber_routes'][$id];
  }


}

?>
