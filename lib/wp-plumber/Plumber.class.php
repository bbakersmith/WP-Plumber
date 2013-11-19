<?php

class Plumber {


  public static function set_views_directory($dir) {
    $full_path = get_stylesheet_directory().'/'.$dir.'/';
    self::set_global('views_directory', $full_path);
  }


  public static function set_view_render_fn($fn) {
    self::set_global('view_render_fn', $fn);
  }


  public static function set_routes($routes) {
    self::set_global('routes', $routes);
  }


  public static function set_route_templates($templates) {
    self::set_global('route_templates', $templates);
  }


  private static function set_global($key, $val) {
    $GLOBALS['wp_plumber_user_defined'][$key] = $val;
  }


  public static function set_alias($alias_hash) {
    foreach($alias_hash as $alias => $target) {
      // TODO
      // create a special bare bones route with following callback:
      //
      // header('Location: ' . $url, true, $statusCode);
      // die();
    }
  }


  public static function create_routes() {
    $args = $GLOBALS['wp_plumber_user_defined'];
    PlumberRouteFactory::create_routes($args);
var_dump($GLOBALS['wp_plumber_routes']);
  }


  public static function callback() {
    // first callback arg is id, the rest are query_vars
    $args = func_get_args();
    $id = $args[0];
    $page_arguments = array_slice($args, 1);
    self::get_route_by_id($id)->callback($page_arguments);
  }


  public static function create_router_definitions($router) {
    $wp_router_definitions = self::generate_route_definitions();
    foreach($wp_router_definitions as $route => $definition) {
      $router->add_route($definition['path'], $definition);
    }
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
