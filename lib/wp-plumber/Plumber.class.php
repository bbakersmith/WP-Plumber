<?php

class Plumber {

  private static $plumber_route_class = 'PlumberRoute';
  private static $plumber_pod_class = 'PlumberPod';

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
    self::$routes = static::create_routes_with_factory(
      self::$route_definitions,
      self::$route_templates
    );

    $all_routes = self::$routes;
    $wp_router_definitions = static::get_wp_router_definitions($all_routes);
    foreach($wp_router_definitions as $route => $definition) {
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
    $pre_render = $route->get_pre_render();
    $render_args = static::user_callback($pre_render, $pre_render_args);

    // render view if view_template defined
    $template = $route->get_view_template();
    static::render_view_template($template, $render_args);

    // call post render function if it exists
    $post_render = $route->get_post_render();
    static::user_callback($post_render, $render_args);

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


  protected static function create_routes_with_factory($defs, $templates) {
    $factory = new PlumberRouteFactory(self::$plumber_route_class);
    $routes = $factory->create_routes($defs, $templates);
    return $routes;
  }


  protected static function get_all_pod_data($pods, $filters, $route_vars) {
    $factory = new PlumberPodFactory(self::$plumber_pod_class);
    $pods = $factory->create_pods($pods, $filters, $route_vars);
    return $pods;
  }


  private static function get_query_vars($page_arg_keys, $page_arg_vals) {
    if(count($page_arg_keys) > 1) {
      $query_var_keys = array_slice($page_arg_keys, 1);
      $query_var_vals = array_slice($page_arg_vals, 1);
      return array_combine($query_var_keys, $query_var_vals);
    }
    return array();
  }


  protected static function user_callback($function, $args) {
    if($function != false) {
      return call_user_func($function, $args);
    } else {
      return $args;
    }
  }


  protected static function get_wp_router_definitions($all_routes) {
    $all_definitions = array();

    if(count($all_routes) > 0) {
      foreach($all_routes as $route) {
        $all_definitions[$route->get_id()] = $route->get_router_definition();
      }
    }

    return $all_definitions;
  }


  protected static function render_view_template($template, $render_args) {
    if($template != false) {

      $full_views_path = static::get_absolute_views_directory();
      $template_path = $full_views_path.$template;

      $render_fn = self::$view_render_fn;
      call_user_func($render_fn, $template_path, $render_args);

    }
  }


  protected static function get_absolute_views_directory() {
    $theme_dir = get_stylesheet_directory();
    $template_dir = self::$views_directory;
    return $theme_dir.'/'.$template_dir.'/';
  }


}

?>
