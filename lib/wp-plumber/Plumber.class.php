<?php

class Plumber extends PlumberSingleGlobal {


  public $plumber_route_class  = 'PlumberRoute';
  public $plumber_pod_class    = 'PlumberPod';

  protected static $global_key = 'wp_plumber';

  private $debug               = false;
  private $views_directory     = 'views';
  private $view_render         = ''; // TODO
  private $route_templates     = array();
  private $route_definitions   = array();
  private $routes              = array();


  public function __construct() {
    parent::__construct(self::$global_key);
  }


  public function debug($debug_mode=true) {
    $this->debug = $debug_mode;
  }


  public function set_views_directory($dirname) {
    $this->views_directory = $dirname;
  }


  public function get_views_directory() {
    return $this->views_directory;
  }


  public function set_view_render($fn) {
    $this->view_render = $fn;
  }


  public function set_routes($definitions) {
    $this->route_definitions = $definitions;
  }


  public function set_route_templates($templates) {
    $this->route_templates = $templates;
  }


  public static function create_routes($router) {
    $GLOBALS[self::$global_key]->singleton_create_routes($router);
  }


  public function singleton_create_routes($router) {
    $this->routes = $this->create_routes_with_factory(
      $this->route_definitions,
      $this->route_templates
    );

    $all_routes = $this->routes;
    $wp_router_definitions = $this->get_wp_router_definitions($all_routes);
    foreach($wp_router_definitions as $route => $definition) {
      $router->add_route($definition['path'], $definition);
    }
  }


  public static function router_callback() {
    // reference the singleton created when plugin is loaded
    $args = func_get_args();
    $GLOBALS[self::$global_key]->singleton_router_callback($args);
  }


  public function singleton_router_callback($args) {
    // first callback arg is id, the rest are query_vars
    $id = $args[0];
    $route = $this->routes[$id];

    $router_def = $route->get_router_definition();
    $page_arg_keys = $router_def['page_arguments'];
    $query_vars = $this->get_query_vars($page_arg_keys, $args);

    $route_vars = $route->get_route_vars();
    $query_and_route_vars = array_merge($query_vars, $route_vars);

    // parse and process pods
    $pre_render_args = $this->get_all_pod_data(
      $route->get_pods(),
      $route->get_pod_filters(), 
      $query_and_route_vars
    );
    $pre_render_args['route_vars'] = $query_and_route_vars;

    $pre_render = $route->get_pre_render();
    $render_args = $this->user_callback($pre_render, $pre_render_args);

    // render view if view_template defined
    $template = $route->get_view_template();
    $this->render_view_template($template, $render_args);

    // call post render function if it exists
    $post_render = $route->get_post_render();
    $this->user_callback($post_render, $render_args);

    if($this->debug == true) {
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


  protected function create_routes_with_factory($defs, $templates) {
    $factory = new PlumberRouteFactory($this->plumber_route_class);
    $routes = $factory->create_routes($defs, $templates);
    return $routes;
  }


  protected function get_all_pod_data($pods, $filters, $route_vars) {
    $factory = new PlumberPodFactory($this->plumber_pod_class);
    $pods = $factory->create_pods($pods, $filters, $route_vars);
    return $pods;
  }


  private function get_query_vars($page_arg_keys, $page_arg_vals) {
    if(count($page_arg_keys) > 1) {
      $query_var_keys = array_slice($page_arg_keys, 1);
      $query_var_vals = array_slice($page_arg_vals, 1);
      return array_combine($query_var_keys, $query_var_vals);
    }
    return array();
  }


  protected function user_callback($function, $args) {
    $response = false;
    if($function != false) {
      $response = call_user_func($function, $args);
    }

    if($response != false && is_array($response)) {
      return $response;
    } else {
      return $args;
    }
  }


  protected function get_wp_router_definitions($all_routes) {
    $all_definitions = array();

    if(count($all_routes) > 0) {
      foreach($all_routes as $route) {
        $all_definitions[$route->get_id()] = $route->get_router_definition();
      }
    }

    return $all_definitions;
  }


  protected function render_view_template($template, $render_args) {
    if($template != false) {

      $full_views_path = $this->get_absolute_views_directory();
      $template_path = $full_views_path.$template;

      $render_fn = $this->view_render;
      call_user_func($render_fn, $template_path, $render_args);

    }
  }


  protected function get_absolute_views_directory() {
    $theme_dir = get_stylesheet_directory();
    $template_dir = $this->views_directory;
    return $theme_dir.'/'.$template_dir.'/';
  }


}

?>
