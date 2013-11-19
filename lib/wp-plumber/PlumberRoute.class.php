<?php

class PlumberRoute {


  public $id, $plumber_definition, $router_definition;


  function __construct($definition) {
    $this->id = $definition['id'];
    $this->plumber_definition = $definition;
    $this->router_definition = self::build_router_definition($definition);
  }


  public function callback($page_arguments=array()) {
    $query_vars = self::get_query_vars($page_arguments);
  
    // parse and process pods
    $pre_render_args = PlumberPods::get($this, $query_vars);
    $pre_render_args['query_vars'] = $query_vars;

    $render_args = self::preprocessor($pre_render_args);
    $template = $this->plumber_definition['view_template'];
    $template_path = $GLOBALS['wp_plumber_user_defined']['views_directory'];
    $render_fn = $GLOBALS['wp_plumber_user_defined']['view_render_fn'];
    call_user_func($render_fn, $template_path.$template, $render_args);

    self::postprocessor($render_args);
  }


  private function get_query_vars($page_arg_vals) {
    if(count($page_arg_vals) > 0) {
      $router_definition = $this->router_definition['page_arguments'];
      $arg_keys = array_slice($router_definition, 1);
      $arg_hash = array_combine($arg_keys, $page_arg_vals);
      return $arg_hash;
    }
    return false;
  }


  private function build_router_definition($definition) {
    $new_definition = array(
      'path' => self::build_path($definition['path']),
      'page_callback' => __NAMESPACE__.'\Plumber::callback',
      'template' => false,
      'query_vars' => array(),
      'page_arguments' => array()
    );

    $vars_and_args = self::build_vars_and_args($definition);

    $router_definition = array_merge($new_definition, $vars_and_args);
    return $router_definition;
  }


  private function build_path($plumber_path) {
    $new_path = preg_replace('/:([^\/\s]+)/', '(.*)', $plumber_path);

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
    $vars_match = self::parse_vars($definition['path']);
    $vars = $vars_match[1];

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
