<?php

class Plumber {


  public static function initialize_routes($args) {
    global $wp_plumber_routes;

    if(array_key_exists('routes', $args)) {

      if(array_key_exists('route_templates', $args)) {
        $routes = self::apply_all_route_templates(
          $args['routes'],
          $args['route_templates']
        );
      } else {
        $routes = $args['routes'];
      }

      $wp_plumber_routes = $routes;

    }
  }


  public static function get_route_definitions($router) {
    $wp_router_definitions = self::generate_wp_router_definitions();
    foreach($wp_router_definitions as $route => $definition) {
      $router->add_route($route, $definition);
    }
  }


  private static function generate_route_definitions() {
    global $wp_plumber_routes;

    $all_definitions = array();

    foreach($wp_plumber_routes as $route => $definition) {
      $total_params = self::find_total_query_params($definition);
      $query_vars = array();
      $page_arguments = array();

      for($param = 1; $param <= $total_params; $param++) {
        $query_vars[''.$param] = $param;
        array_push($page_arguments, $param);
      }

      $wp_router_definition = array(
        $route => array(
          'path' => $route,
          'page_callback' => __NAMESPACE__.'\Plumber.render_page',
          'template' => false,
          'query_vars' => $query_vars,
          'page_arguments' => $page_arguments
        )
      );

      array_push($all_definitions, $wp_route_definition);
    }

    return $all_definitions;
  }


  public static function render_page() {
    $args = func_get_args();
    print_r($args);
  }


  private static function apply_all_route_templates($routes, $templates) {
    $all_applied_routes = array();

    // merge to generate initial arg set
    foreach($routes as $route => $definition) {
      if(array_key_exists('route_template', $definition)) {
        $new_definition = self::apply_route_template($definition, $templates);
      }
      $all_applied_routes[$route] = $new_definition;
    }
    return $all_applied_routes;
  }


  private static function apply_route_template($definition, $templates) {
    if(array_key_exists($definition['route_template'], $templates)) {
      // start from the template
      $base_definition = $templates[$definition['route_template']];
      unset($definition['route_template']);

      // add pods
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
        return self::apply_route_template($merged_definition, $templates);
      } else {
        return $merged_definition;
      }
    }
  }


  private static function register_route_definitions($route_definitions) {

  }


  private static function theme_dir($dirname) {
    return get_stylesheet_directory().'/'.$dirname.'/';
  }


  // TODO (MAYBE) postprocessor, inline pods filter/sort definitions

}


class PlumberRouteDefinition {

  // create router definition

    // path = key
    
    // args include all info needed by callback to get pods and render view

}


class PlumberRouteCallback {
  
  function __call($method, $args) {

  }


  // callback fetches specified pods

  // combines pods with url_args and additional_args

  // passes combined args (with pods) to preprocessor function

  // passes preprocessor results to view template

}

?>
