<?php

require_once(dirname(__FILE__).'/PlumberRoute.php');
require_once(dirname(__FILE__).'/PlumberRouteFactory.php');

class Plumber {


  public static function register_routes($args) {
    PlumberRouteFactory::create_routes($args);
  }


  public static function callback() {
    // first callback arg is id, the rest are query_vars
    $args = func_get_args();
    $id = $args[0];
    $page_arguments = array_slice($args, 1);
    self::get_route_by_id($id)->callback($page_arguments);
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
      $all_definitions[$route->id] = $route->router_definition;
    }
    return $all_definitions;
  }


  // TODO TODO TODO TODO
  public static function render_liquid_template($template, $args = array()) {
    // TODO also need to add liquid lib registration...
    global $liquid;

    $template_path = THEME_ROOT.'/views/'.$template.'.liquid';
    $liquid->parse(file_get_contents($template_path));

    print $liquid->render($args);
  }


  private static function get_route_by_id($id) {
    return $GLOBALS['wp_plumber_routes'][$id];
  }


  // TODO support for postprocessor function

}

?>
