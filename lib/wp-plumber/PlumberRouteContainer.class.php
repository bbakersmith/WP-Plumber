<?php

class PlumberRouteContainer {


  private $path;

  private $rank;

  private $http_method_routes = array(
    'DELETE' => null,
    'GET' => null,
    'POST' => null,
    'PUT' => null
  );


  function __construct($path, $rank) {
    $this->path = $path;
    $this->rank = $rank;
  }


  public function get_path() {
    return $this->path;
  }


  public function get_rank() {
    return $this->rank;
  }


  public function get_route($http_method='GET') {
    $route = $this->http_method_routes[$http_method];
    return $route;
  }


  public function set_route($route, $http_method='GET') {
    $this->http_method_routes[$http_method] = $route;
  }


  public function get_router_definition() {
    $router_definition = $this->build_standard_definition($this->path);
    return $router_definition;
  }


  private function build_standard_definition($path) {
    $new_definition = array(
      'path' => $this->build_path($path),
      'page_callback' => array(
        'DELETE' => 'PlumberStatic::router_callback_delete',
        'GET' => 'PlumberStatic::router_callback_get',
        'POST' => 'PlumberStatic::router_callback_post',
        'PUT' => 'PlumberStatic::router_callback_put',
        'default' => 'PlumberStatic::router_callback_get'
      ),
      'template' => false,
      'query_vars' => array(),
      'page_arguments' => array()
    );

    $vars_and_args = $this->build_vars_and_args($path);

    $router_definition = array_merge($new_definition, $vars_and_args);
    return $router_definition;
  }


  private function build_path($plumber_path) {
    $new_path = preg_replace('/{([^\/\s]+)}/', '(.*)', $plumber_path);

    // convert homepage symbol for WP Router
    if($new_path == '^') {
      $final_path = '$';

    // convert catch-all symbol for WP Router
    } else if($new_path == '*') {
      $final_path = '.*$';
    } else {
      $final_path = '^'.$new_path.'$';
    }

    return $final_path;
  }  


  private function build_vars_and_args($path) {
    $query_vars = array('plumber_route_id' => ''.$path);
    $page_arguments = array('plumber_route_id');
    $vars_match = $this->parse_vars($path);
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
    preg_match_all('/{([^\/\s]+)}/', $path, $all_matches);
    return $all_matches;
  }


}

?>
