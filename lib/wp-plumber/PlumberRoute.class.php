<?php

class PlumberRoute {


  private $definition = array(
    'id' => '', //
    'path' => '', //
    'http_method' => 'GET',
    'view_template' => false,
    'pods' => array(),
    'pod_filters' => array(),
    'pre_render' => array(),
    'post_render' => array(),
    'route_vars' => array()
  );


  function __construct($definition) {
    $this->definition = array_merge($this->definition, $definition);
  }


  public function get_id() { 
    return $this->get_generic_attribute('id');
  }


  public function get_http_method() {
    return $this->get_generic_attribute('http_method');
  }


  public function get_pods() {
    return $this->get_generic_attribute('pods');
  }


  public function get_pod_filters() {
    return $this->get_generic_attribute('pod_filters');
  }


  public function get_route_vars() {
    return $this->get_generic_attribute('route_vars', array());
  }


  public function get_view_template() {
    return $this->get_generic_attribute('view_template');
  }


  public function get_pre_render() {
    return $this->get_generic_attribute('pre_render');
  }


  public function get_post_render() {
    return $this->get_generic_attribute('post_render');
  }


  private function get_generic_attribute($attribute, $default=false) {
    if(array_key_exists($attribute, $this->definition)) {
      return $this->definition[$attribute];
    } else {
      return $default;
    }
  }


  private function theme_dir($dirname) {
    return get_stylesheet_directory().'/'.$dirname.'/';
  }


}

?>
