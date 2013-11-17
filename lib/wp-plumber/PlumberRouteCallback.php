<?php

class PlumberRouteCallback {


  public static function call() {
    $args = func_get_args();
    print_r($args);
    $id = $args[0];
    $page_arguments = array_slice($args, 1);
    $query_vals = self::match_page_args_with_keys($id, $page_arguments);
    return $query_vals;
  }


  private static function match_page_args_with_keys($id, $arg_vals) {
    $route = self::get_route_by_id($id);
    $router_definition = $route->router_definition[$id];
    $arg_keys = array_slice($router_definition['page_arguments'], 1);
    $arg_hash = array_combine($arg_keys, $arg_vals);
    return $arg_hash;
  }


  private static function get_route_by_id($id) {
    return $GLOBALS['wp_plumber_routes'][$id];
  }
  

  // callback fetches specified pods

  // combines pods with url_args and additional_args

  // passes combined args (with pods) to preprocessor function

  // passes preprocessor results to view template

  private static function theme_dir($dirname) {
    return get_stylesheet_directory().'/'.$dirname.'/';
  }


}

?>
