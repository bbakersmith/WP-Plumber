<?php

class PlumberRoute {


  private $definition = array(
    'id' => 999,
    'path' => '',
    'view_template' => false,
    'pods' => array(),
    'pod_filters' => array(),
    'route_template' => false,
    'pre_render_fn' => false,
    'post_render_fn' => false,
    'alias' => false
  );


  function __construct($definition) {
    $this->definition = array_merge($this->definition, $definition);
  }


  public function get_id() {
    return self::get_generic_attribute('id');
  }


  public function get_pods() {
    return self::get_generic_attribute('pods');
  }


  public function get_pod_filters() {
    return self::get_generic_attribute('pod_filters');
  }


  public function get_route_vars() {
    return self::get_generic_attribute('route_vars', array());
  }


  public function get_view_template() {
    return self::get_generic_attribute('view_template');
  }


  public function get_pre_render_fn() {
    return self::get_generic_attribute('pre_render_fn');
  }


  public function get_post_render_fn() {
    return self::get_generic_attribute('post_render_fn');
  }


  public function get_alias() {
    return self::get_generic_attribute('alias');
  }


  private function get_generic_attribute($attribute, $default=false) {
    if(array_key_exists($attribute, $this->definition)) {
      return $this->definition[$attribute];
    } else {
      return $default;
    }
  }


  public function get_router_definition() {
    $router_definition = self::build_standard_definition($this->definition);
    return $router_definition;
  }


  private function build_standard_definition($plumber_definition) {
    $new_definition = array(
      'path' => self::build_path($plumber_definition['path']),
      'page_callback' => 'Plumber::router_callback',
      'template' => false,
      'query_vars' => array(),
      'page_arguments' => array()
    );

    $vars_and_args = self::build_vars_and_args($plumber_definition);

    $router_definition = array_merge($new_definition, $vars_and_args);
    return $router_definition;
  }


  private function build_path($plumber_path) {
    $new_path = preg_replace('/:([^\/\s]+)/', '(.*)', $plumber_path);

    if($new_path == '$') {
      $final_path = '$';
    } else if($new_path == '*') {
      $final_path = '.*';
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


  private function parse_vars($path) {
    preg_match_all('/:([^\/\s]+)/', $path, $all_matches);
    return $all_matches;
  }


  private function theme_dir($dirname) {
    return get_stylesheet_directory().'/'.$dirname.'/';
  }


}

?>
