<?php

class PlumberRoute {


  public $id, $plumber_definition, $router_definition;


  function __construct($definition) {
    $this->id = $definition['id'];
    $this->plumber_definition = $definition;
    $this->router_definition = self::build_router_definition($definition);
  }


  public function callback($page_arguments) {
    $query_vars = self::get_query_vars($page_arguments);
  
    // parse and process pods
    $pre_render_args = self::parse_and_process_pods($query_vars);
    $pre_render_args['query_vars'] = $query_vars;

    $render_args = self::preprocessor($pre_render_args);

    $template = $this->plumber_definition['view_template'];
    Plumber::render_liquid_template($template, $render_args);

    self::postprocessor($render_args);
  }


  private function get_query_vars($page_arg_vals) {
    $router_definition = $this->router_definition['page_arguments'];
    $arg_keys = array_slice($router_definition, 1);
    $arg_hash = array_combine($arg_keys, $page_arg_vals);
    return $arg_hash;
  }


  private function parse_and_process_pods($query_vars) {
    // pods definition syntax
    //
    // thing                  all pods of the given type
    // something:thing        all pods of the given type with named var
    // thing[var]             single pod with query_var (match id or slug)
    //
    $all_pods = array();

    $pod_strings = $this->plumber_definition['pods'];
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


  private function build_router_definition($definition) {
    $new_definition = array(
      'path' => self::build_path($definition['path']),
      'page_callback' => __NAMESPACE__.'\Plumber.callback',
      'template' => false,
      'query_vars' => array(),
      'page_arguments' => array()
    );

    $vars_and_args = self::build_vars_and_args($definition);

    $router_definition = array_merge($new_definition, $vars_and_args);
    return $router_definition;
  }


  private function build_path($plumber_path) {
    $new_path = preg_replace('/:([^\/\s]+)/', '([^\/\s]+)', $plumber_path);

    if($new_path == '') {
      $final_path = '$';
    } else {
      $final_path = '^'.$new_path.'$';
    }

    return $final_path;
  }  


  private function build_vars_and_args($definition) {
    $query_vars = array('plumber_route_id' => ''.$definition['id']);
    $page_arguments = array('plumber_route_id');
    $vars = self::parse_vars($definition['path'])[1];

    if(count($vars) > 0) {
      foreach($vars as $k => $v) {
        $query_vars[$v] = $k + 1;
        array_push($page_arguments, $v);
      }
    }

    $vars_and_args = array(
      'query_vars' => $query_vars,
      'page_arguments' => $page_arguments
    );
    return $vars_and_args;
  }


  private function preprocessor($args) {
    $result = self::user_defined_callback('preprocessor', $args);
    return $result ? $result : $args;
  }


  private function postprocessor($args) {
    return self::user_defined_callback('postprocessor', $args);
  }


  private function user_defined_callback($callback_key, $args) {
    $plumber_definition = $this->plumber_definition;
    if(isset($route[$callback_key])) {
      $callback = $plumber_definition[$callback_key];
      return call_user_func($callback, $args);
    }
    return false;
  }


  private function parse_vars($path) {
    preg_match_all('/:([^\/\s]+)/', $path, $all_matches);
    return $all_matches;
  }


  private function theme_dir($dirname) {
    return get_stylesheet_directory().'/'.$dirname.'/';
  }


}

?>
