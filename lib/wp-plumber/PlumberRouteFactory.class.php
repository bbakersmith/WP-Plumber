<?php

class PlumberRouteFactory {


  protected $_class_to_create;

  protected $default_route_template = '_default';

  protected $cumulative_attributes = array('pods', 'pod_filters');

  
  function __construct($class) {
    // assign the class that is to be created by this factory
    $this->_class_to_create = $class;
  }  


  public function create_routes($definitions, $templates=array()) {
    $containers = array();

    foreach($definitions as $http_method => $full_def) {
      foreach($full_def as $path => $def) {
        $applied_def = $this->apply_template($def, $templates);

        if(array_key_exists($path, $containers)) {
          $container = $containers[$path];
        } else {
          $container = new PlumberRouteContainer($path, $def['rank']);
        }

        // in case id might ever be something other than path
        $applied_def['id'] = $path;
        $the_route = self::create_route_object($path, $applied_def);

        $container->set_route($the_route, $http_method);
        $containers[$path] = $container;
      }
    }

    return $containers;
  }


  private function apply_template($definition, $templates) {
    $last = false;
    if(array_key_exists('route_template', $definition)) {
      if($definition['route_template'] == false) {
        // do not apply any template if route_template is defined false
        // or the last flag has been set to true
        return $definition;
      } 
    } else {
      // assume 'default' if route_template not defined
      $definition['route_template'] = $this->default_route_template;
      $last = true;
    }

    if(array_key_exists($definition['route_template'], $templates)) {
      // start from the template
      $base_definition = $templates[$definition['route_template']];
      unset($definition['route_template']);

      $cumulative_definitions = $this->merge_cumulative_vals(
        $definition,
        $base_definition
      );

      $merged_definition = array_merge(
        $base_definition, 
        $definition,
        $cumulative_definitions
      );

      if($last == false) {
        return $this->apply_template($merged_definition, $templates);
      } else {
        $definition = $merged_definition;
      }
    }

    return $definition;
  }


  private function merge_cumulative_vals($def, $old_def) {
    $new_def = array();
    foreach($this->cumulative_attributes as $key) {

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


  private function create_route_object($path, $definition) {
    $definition['path'] = $path;
    return new $this->_class_to_create($definition);
  }


}

?>
