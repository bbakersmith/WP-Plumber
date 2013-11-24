<?php

class Plumber {


  private static $debug               = false;
  private static $views_directory     = 'views';
  private static $view_render_fn      = ''; // TODO
  private static $route_templates     = array();
  private static $route_definitions   = array();
  private static $routes              = array();


  public static function debug($debug_mode=false) {
    self::$debug = $debug_mode;
  }


  public static function set_views_directory($dirname) {
    self::$views_directory = $dirname;
  }


  public static function get_views_directory() {
    return self::$views_directory;
  }


  public static function set_view_render_fn($fn) {
    self::$view_render_fn = $fn;
  }


  public static function set_routes($definitions) {
    self::$route_definitions = $definitions;
  }


  public static function set_route_templates($templates) {
    self::$route_templates = $templates;
  }


  public static function create_routes($router) {
    self::$route_definitions = PlumberRouteFactory::apply_route_templates(
      self::$route_definitions,
      self::$route_templates
    );
    self::$routes = PlumberRouteFactory::create_routes(
      self::$route_definitions
    );

    $router_definitions = static::get_router_definitions();
    foreach($router_definitions as $route => $definition) {
      $router->add_route($definition['path'], $definition);
    }
  }


  public static function router_callback() {
    // first callback arg is id, the rest are query_vars
    $args = func_get_args();

    $id = $args[0];
    $route = self::$routes[$id];

    $router_def = $route->get_router_definition();
    $page_arg_keys = $router_def['page_arguments'];
    $query_vars = static::get_query_vars($page_arg_keys, $args);

    $route_vars = $route->get_route_vars();
    $query_and_route_vars = array_merge($query_vars, $route_vars);

    // parse and process pods
    $pre_render_args = static::get_all_pod_data(
      $route->get_pods(),
      $route->get_pod_filters(), 
      $query_and_route_vars
    );

    $pre_render_args['query_vars'] = $query_and_route_vars;

    // TODO DRY
    $pre_render_fn = $route->get_pre_render_fn();
    if($pre_render_fn != false) {
      $render_args = call_user_func($pre_render_fn, $pre_render_args);
    } else {
      $render_args = $pre_render_args;
    }

    // render view if view_template defined
    $template = $route->get_view_template();
    if($template != false) {
      $theme_dir = get_stylesheet_directory();
      $template_dir = self::$views_directory;
      $template_path = $theme_dir.'/'.$template_dir.'/'.$template;

      $render_fn = self::$view_render_fn;
      call_user_func($render_fn, $template_path, $render_args);
    }

    // TODO DRY
    $post_process_fn = $route->get_pre_render_fn();
    if($post_process_fn != false) {
      call_user_func($post_process_fn, $pre_render_args);
    }

    if(self::$debug == true) {
      print '<hr /><h3>WP Plumber DEBUG</h3><hr />';
      print '<h5>$route</h5><hr />';
      var_dump($route);
      print '<hr /><h5>$route->get_router_definition()</h5><hr />';
      var_dump($route->get_router_definition());
      print '<hr /><h5>$query_and_route_vars</h5><hr />';
      var_dump($query_and_route_vars); 
      print '<hr /><h5>$pre_render_args</h5><hr />';
      var_dump($pre_render_args);
      print '<hr /><h5>$render_args</h5><hr />';
      var_dump($render_args);
    }
  }


  private static function get_all_pod_data($pods, $filters, $route_vars) {
    return Plumber::get($pods, $filters, $route_vars);
  }


  private static function get_query_vars($page_arg_keys, $page_arg_vals) {
    if(count($page_arg_keys) > 1) {
      $query_var_keys = array_slice($page_arg_keys, 1);
      $query_var_vals = array_slice($page_arg_vals, 1);
      return array_combine($query_var_keys, $query_var_vals);
    }
    return array();
  }


  private static function get_router_definitions() {
    $all_definitions = array();
    foreach(self::$routes as $route) {
      $all_definitions[$route->get_id()] = $route->get_router_definition();
    }
    return $all_definitions;
  }


}

?>
