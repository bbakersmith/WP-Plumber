<?php

class Plumber {

  protected $static_class = 'PlumberStatic';

  public $plumber_route_factory = null;
  public $plumber_route_class  = 'PlumberRoute';

  public $plumber_pod_factory = null;
  public $plumber_pod_class    = 'PlumberPod';

  private $debug               = false;
  private $views_directory     = 'views';
  private $render              = '';        // TODO add default render fn
  private $route_templates     = array();
  private $route_definitions   = array(
    'DELETE' => array(),
    'GET' => array(),
    'POST' => array(),
    'PUT' => array()
  );
  private $routes              = array();


  public function __construct($args) {

    $construction_keys = array(
      'views_directory', 
      'render'
    );

    foreach($construction_keys as $index => $key) {
      // key must exist in array of valid construction arguments and
      // it must have a default setting defined in the plumber instance
      if(array_key_exists($key, $args) && isset($this->$key)) {
        $this->$key = $args[$key];
      }
    }

    $sclass = $this->static_class;
    $sclass::set_active_instance($this);
  }


  public function __call($method, $args) {
    // if router_callback_get (etc) method is called, convert it for
    // standard router_callback method
    if(preg_match('/^router_callback_(.*)$/', $method, $matches)) {
      $http_method = strtoupper($matches[1]);
      $sclass = $this->static_class;
      $sclass::router_callback($args, $http_method);
    }
  }


  public function debug($debug_mode=true) {
    $this->debug = $debug_mode;
  }


  public function set_views_directory($dirname) {
    $this->views_directory = $dirname;
  }


  public function get_views_directory() {
    $relative_dir =  $this->views_directory;
    return $relative_dir;
  }


  public function set_render($fn) {
    $this->render = $fn;
  }


  public function delete($path, $def) {
    $this->add_route('DELETE', $path, $def);
  }


  public function get($path, $def) {
    $this->add_route('GET', $path, $def);
  }


  public function post($path, $def) {
    $this->add_route('POST', $path, $def);
  }


  public function put($path, $def) {
    $this->add_route('PUT', $path, $def);
  }


  private function add_route($http_method, $path, $def) {
    $total = 0;
    foreach($this->route_definitions as $index => $definitions) {
      $total = $total + count($definitions);
    }
    $def['rank'] = $total;
    $this->route_definitions[$http_method][$path] = $def;
  } 


  public function route_template($name, $template) {
    $this->route_templates[$name] = $template;
  }


  public function create_routes($router) {
    $this->routes = $this->create_routes_with_factory(
      $this->route_definitions,
      $this->route_templates
    );

    // sort routes by rank, allowing the path to be used as id
    $all_route_containers = $this->routes;
    $route_ranks = array();
    foreach($all_route_containers as $key => $container) {
      $route_ranks[$key] = $container->get_rank();
    }
    array_multisort($route_ranks, SORT_ASC, $all_route_containers);

    $router_definitions = $this->get_wp_router_definitions($all_route_containers);
    foreach($router_definitions as $route => $definition) {
      $router->add_route($definition['path'], $definition);
    }
  }


  public function router_callback($args, $http_method='GET') {
    // first callback arg is id, the rest are query_vars
    $path = $args[0];
    $route_container = $this->routes[$path];
    $route = $route_container->get_route($http_method);

    $router_def = $route_container->get_router_definition();
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

    // call pre render function(s) if exists
    $pre_render = $route->get_pre_render();
    $render_args = $pre_render_args;
    foreach($pre_render as $index => $function) {
      $render_args = $this->user_callback($function, $render_args);
    }

    // render view if view_template defined
    $template = $route->get_view_template();
    $this->render_view_template($template, $render_args);

    // call post render function(s) if exists
    $post_render = $route->get_post_render();
    foreach($post_render as $index => $function) {
      $this->user_callback($function, $render_args);
    }

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
    // if factory object has been injected, use that. otherwise
    // create a new factory object using the default class
    if($this->plumber_route_factory == null) {
      $this->plumber_route_factory = new PlumberRouteFactory(
        $this->plumber_route_class
      );
    }
    $factory = $this->plumber_route_factory;
    $routes = $factory->create_routes($defs, $templates);
    return $routes;
  }


  protected function get_all_pod_data($pods, $filters, $route_vars) {
    // if factory object has been injected, use that. otherwise
    // create a new factory object using the default class
    if($this->plumber_pod_factory == null) {
      $this->plumber_pod_factory = new PlumberPodFactory(
        $this->plumber_pod_class
      );
    }
    $factory = $this->plumber_pod_factory;
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


  protected function get_wp_router_definitions($all_route_containers) {
    $all_definitions = array();

    if(count($all_route_containers) > 0) {
      foreach($all_route_containers as $container) {
        $definition = $container->get_router_definition();
        $all_definitions[$container->get_path()] = $definition;
      }
    }

    return $all_definitions;
  }


  protected function render_view_template($template, $render_args) {
    if($template != false) {
      $full_views_path = $this->get_absolute_views_directory();
      $template_path = $full_views_path.$template;

      $render_fn = $this->render;
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
