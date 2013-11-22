<?php

class Plumber {


  private static $views_directory     = 'views';
  private static $view_render_fn      = ''; // TODO
  private static $route_templates     = array();
  private static $route_definitions   = array();
  private static $routes              = array();


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

//
$r = self::$routes[0];
// first route
var_dump($r);
// first route's router definition
var_dump($r->get_router_definition());
// all routes
foreach(self::$routes as $key => $value) {
  var_dump($value->get_router_definition());
}
//

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



// ALIAS ROUTE HANDLING IN PROGRESS


print "CALLBACK||";
$route_alias = $route->get_alias();
var_dump($route_alias);
if($route->get_alias() != false) {
  $all_defs = self::get_router_definitions();
  foreach($all_defs as $match_id => $def) {
    $alias_match = preg_match_all('/'.$def['path'].'/', $route_alias, $alias_vars);
print $def['path'];
print '||';
print $route_alias;
    if($alias_match > 0) {
      print "FUCK YES";
var_dump($def);
      // overwrite defaults with alias details
      $id = $match_id;
      $route = self::$routes[$id];
      // spoof query vars with regex matches
var_dump($alias_vars);
      $args = array_merge(array($id), $alias_vars[0]);
print "---";
      var_dump($args); 
print "____";
    } else {
      print "FUCK NO";
    }
  }
}




    $router_def = $route->get_router_definition();
    $page_arg_keys = $router_def['page_arguments'];
    $query_vars = self::get_query_vars($page_arg_keys, $args);
var_dump($router_def);
    $route_vars = $route->get_route_vars();
    $query_and_route_vars = array_merge($query_vars, $route_vars);

print "QUERY AND ROUTE VARS";
var_dump($query_and_route_vars); 

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

var_dump($render_args);

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
        if(array_key_exists('route_template', $definition)) {
          $new_definition = self::apply_a_template($definition, $templates);
        } else {
          $new_definition = $definition;
        }
        $all_applied_definitions[$path] = $new_definition;
      }

      return $all_applied_definitions;
    } else {
      return $route_defs;
    }
  }


  private static function apply_a_template($definition, $templates) {
print "+++++++++++++";
var_dump($definition);
var_dump($templates);
print "=============";
    if(array_key_exists($definition['route_template'], $templates)) {
      // start from the template
      $base_definition = $templates[$definition['route_template']];
      unset($definition['route_template']);

      // merge pods rather than overwrite
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
        return self::apply_a_template($merged_definition, $templates);
      } else {
        return $merged_definition;
      }
    }
  }


  // need to mock create_router_definitions
  private static function get_router_definitions() {
    $all_definitions = array();
    foreach(self::$routes as $route) {
      $all_definitions[$route->get_id()] = $route->get_router_definition();
    }
    return $all_definitions;
  }


}

?>
