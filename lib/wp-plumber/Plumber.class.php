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
    $new_defs = self::apply_templates($definitions, self::$route_templates);
    self::$route_definitions = $new_defs;
  }


  public static function set_route_templates($templates) {
    $new_defs = self::apply_templates(self::$route_definitions, $templates);
    self::$route_definitions = $new_defs;
    self::$route_templates = $templates;
  }


  public static function create_routes($router) {
    self::$routes = PlumberRouteFactory::create_routes(
      self::$route_definitions
    );

    $router_definitions = self::get_router_definitions();
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
    $query_vars = self::get_query_vars($page_arg_keys, $args);

    $route_vars = $route->get_route_vars();
    $query_and_route_vars = array_merge($query_vars, $route_vars);

    // parse and process pods
    $pre_render_args = PlumberPods::get(
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


  private static function get_query_vars($page_arg_keys, $page_arg_vals) {
    if(count($page_arg_keys) > 1) {
      $query_var_keys = array_slice($page_arg_keys, 1);
      $query_var_vals = array_slice($page_arg_vals, 1);
      return array_combine($query_var_keys, $query_var_vals);
    }
    return array();
  }


  private static function apply_templates($route_defs, $templates) {
    if(count($route_defs) > 0 && count($templates) > 0) {
      $all_applied_route_defs = array();

      // merge to generate initial arg set
      foreach($route_defs as $path => $definition) {
        $new_definition = self::apply_a_template($definition, $templates);
        $all_applied_definitions[$path] = $new_definition;
      }

      return $all_applied_definitions;
    } else {
      return $route_defs;
    }
  }


  private static function apply_a_template($definition, $templates) {
    if(array_key_exists('route_template', $definition)) {
      if($definition['route_template'] == false) {
        // do not apply any template if route_template is defined false
        // or the last flag has been set to true
        return $definition;
      } 
    } else {
      // assume 'default' if route_template not defined
      $definition['route_template'] = 'default';
    }
    
    if($definition['route_template'] == 'default') {
      $last = true;
    } else {
      $last = false;
    }

    if(array_key_exists($definition['route_template'], $templates)) {
      // start from the template
      $base_definition = $templates[$definition['route_template']];
      unset($definition['route_template']);

      $cummulative_attribute_names = array('pods', 'pod_filters');
      $cummulative_definitions = self::merge_cummulative_vals(
        $definition,
        $base_definition, 
        $cummulative_attribute_names
      );

      $merged_definition = array_merge(
        $base_definition, 
        $definition,
        $cummulative_definitions
      );

// print '<hr/>';
// print '<hr/>';
// var_dump($definition);
// print '<hr/>';
// var_dump($merged_definition);
// print '<hr/>';
// print '<hr/>';

      if($last == false) {
        return self::apply_a_template($merged_definition, $templates);
      }
    }

    return $definition;
  }


  // need to mock create_router_definitions
  private static function get_router_definitions() {
    $all_definitions = array();
    foreach(self::$routes as $route) {
      $all_definitions[$route->get_id()] = $route->get_router_definition();
    }
    return $all_definitions;
  }


  private static function merge_cummulative_vals($def, $old_def, $key_names) {
    $new_def = array();
    foreach($key_names as $key) {

      // merge pods rather than overwrite
      if(array_key_exists($key, $def) &&
         array_key_exists($key, $old_def)) {
        $new_def[$key] = array_merge($old_def[$key], $def[$key]);
      } else if(array_key_exists($key, $def)) {
        $new_def[$key] = $def[$key];
      } else if(array_key_exists($key, $old_def)) {
        $new_def[$key] = $old_def[$key];
      } else {
        $new_def[$key] = array();
      }

    }
    return $new_def;
  }

}

?>
