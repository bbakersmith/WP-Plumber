<?php

class PlumberRouteFactory {


  protected $_class_to_create;

  
  function __construct($class) {
    // assign the class that is to be created by this factory
    $this->_class_to_create = $class;
  }  


  public function create_routes($definitions, $templates=array()) {
    $applied_defs = $this->apply_route_templates($definitions, $templates);
    $new_routes = array();
    $rank = 0;
    foreach($applied_defs as $path_def_pair) {
      // the first route to be defined for a given path determines the
      // order in which WP Router will check that path. so if a GET
      // is defined for a specific path then any following POST, PUT,
      // or DELETE definitions will inherit the same path evaluation
      // rank as the GET.
      $path = $path_def_pair[0];
      $def = $path_def_pair[1];
      if(isset($new_routes[$path]) == false) {
        $new_routes[$path] = new PlumberRouteContainer($path, $rank);
      }

      // for backwards compatibility from when ids were not the same as
      // the path
      $def['id'] = $path;
      $created_route = self::create_route_object($path, $def);
      $http_method = $created_route->get_http_method();
      $new_routes[$path]->set_route($created_route, $http_method);
      $rank++;
    }
    return $new_routes;
  }


  private function apply_route_templates($route_defs, $templates) {
    if(count($route_defs) > 0 && count($templates) > 0) {
      $all_applied_route_defs = array();

      // merge to generate route creation definition
      foreach($route_defs as $k => $path_def_pair) {
        $path = $path_def_pair[0];
        $definition = $path_def_pair[1];
        $new_definition = self::apply_a_template($definition, $templates);
        $new_pair = array($path, $new_definition);
        $all_applied_definitions[] = $new_pair;
      }
      return $all_applied_definitions;
    } else {
      return $route_defs;
    }
  }


  private function apply_a_template($definition, $templates) {
    $last = false;
    if(array_key_exists('route_template', $definition)) {
      if($definition['route_template'] == false) {
        // do not apply any template if route_template is defined false
        // or the last flag has been set to true
        return $definition;
      } 
    } else {
      // assume 'default' if route_template not defined
      $definition['route_template'] = '_default';
      $last = true;
    }

    if(array_key_exists($definition['route_template'], $templates)) {
      // start from the template
      $base_definition = $templates[$definition['route_template']];
      unset($definition['route_template']);

      $cummulative_attribute_names = array('pods', 'pod_filters');
      $cummulative_definitions = $this->merge_cummulative_vals(
        $definition,
        $base_definition, 
        $cummulative_attribute_names
      );

      $merged_definition = array_merge(
        $base_definition, 
        $definition,
        $cummulative_definitions
      );

      if($last == false) {
        return $this->apply_a_template($merged_definition, $templates);
      } else {
        $definition = $merged_definition;
      }
    }

    return $definition;
  }


  private function merge_cummulative_vals($def, $old_def, $key_names) {
    $new_def = array();
    foreach($key_names as $key) {

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
