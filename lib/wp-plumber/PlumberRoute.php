<?php

class PlumberRoute {


  public $id, $plumber_definition, $router_definition;


  function __construct($definition) {
    $this->id = $definition['id'];
    $this->plumber_definition = $definition;
    $this->router_definition = self::build_router_definition($definition);
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


  private function parse_vars($path) {
    preg_match_all('/:([^\/\s]+)/', $path, $all_matches);
    return $all_matches;
  }


}

?>
