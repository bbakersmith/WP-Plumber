<?php

class PlumberRouteCallback {


  public static function call() {
    // takes any number of callback arguments
    $args = func_get_args();

    // first callback arg is id, the rest are query_vars
    $id = $args[0];
    $page_arguments = array_slice($args, 1);
    $query_vars = self::match_page_args_with_keys($id, $page_arguments);
  
    // parse and process pods
    $pods = self::parse_and_process_pods($id, $query_vars);

    // package pod results with query_vars and additional_args

    // call preprocessor function with args, if preprocessor given

    // call render function with processed (or not) args

    // call postprocessor function

  }


  private static function match_page_args_with_keys($id, $arg_vals) {
    $route = self::get_route_by_id($id);
    $router_definition = $route->router_definition;
    $arg_keys = array_slice($router_definition['page_arguments'], 1);
    $arg_hash = array_combine($arg_keys, $arg_vals);
    return $arg_hash;
  }


  private static function parse_and_process_pods($id, $query_vars) {
    // pods definition syntax
    //
    // thing                  all pods of the given type
    // something:thing        all pods of the given type with named var
    // thing[var]             single pod with query_var (match id or slug)
    //
    $all_pods = array();

    $pod_strings = self::get_route_by_id($id)->plumber_definition['pods'];
    foreach($pod_strings as $k => $pod_string) {
      // handle alternate args keys
      if(preg_match('/:/', $pod_string)) {
        $parts = explode(':', $pod_string, 2);
        $results_key = $parts[0];
        $pod_string = $parts[1];
      }

      // handle dynamic query vars
      $count = preg_match('/\[([^\]]+)\]/', $pod_string, $query_var_key);
      if($count > 0) {
        $parts = explode('[', $pod_string, 2);
        $pod_string = $parts[0];
        $pod_id_or_slug = $query_vars[$query_var_key[1]];
      }

      // form args
      $results_key = isset($results_key) ? $results_key : $pod_string;
      $pod_type = $pod_string;

      // get pods
      if(isset($pod_id_or_slug)) {
        $pod_results = pods($pod_type, $pod_id_or_slug);
      } else {
        $pod_results = pods($pod_type);
      }

      // add pods to array
      $all_pods[$results_key] = $pod_results;
    }

    return $all_pods;
  }


  private static function get_route_by_id($id) {
    return $GLOBALS['wp_plumber_routes'][$id];
  }

  private static function theme_dir($dirname) {
    return get_stylesheet_directory().'/'.$dirname.'/';
  }


}

?>
