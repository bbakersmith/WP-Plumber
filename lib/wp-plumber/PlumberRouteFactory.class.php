<?php

class PlumberRouteFactory extends PlumberFactory {


  public function create_routes($definitions, $templates=array()) {
    $applied_defs = self::apply_route_templates($definitions, $templates);

    $new_routes = array();
    $rank = 0;
    foreach($applied_defs as $path => $def) {
      $def['id'] = $rank;
      array_push($new_routes, self::create_route_object($path, $def));
      $rank++;
    }
    return $new_routes;
  }


  private function apply_route_templates($route_defs, $templates) {
    if(count($route_defs) > 0 && count($templates) > 0) {
      $all_applied_route_defs = array();

      // merge to generate initial arg set
      foreach($route_defs as $path => $definition) {
        $new_definition = self::apply_a_template($definition, $templates);
        $all_applied_definitions[$path] = $new_definition;
      }

      return $all_applied_definitions;
    } else {
      return $route_defs;
    }
  }


  private function apply_a_template($definition, $templates) {
    if(array_key_exists('route_template', $definition)) {
      if($definition['route_template'] == false) {
        // do not apply any template if route_template is defined false
        // or the last flag has been set to true
        return $definition;
      } 
    } else {
      // assume 'default' if route_template not defined
      $definition['route_template'] = 'default';
    }
    
    if($definition['route_template'] == 'default') {
      $last = true;
    } else {
      $last = false;
    }

    if(array_key_exists($definition['route_template'], $templates)) {
      // start from the template
      $base_definition = $templates[$definition['route_template']];
      unset($definition['route_template']);

      $cummulative_attribute_names = array('pods', 'pod_filters');
      $cummulative_definitions = self::merge_cummulative_vals(
        $definition,
        $base_definition, 
        $cummulative_attribute_names
      );

      $merged_definition = array_merge(
        $base_definition, 
        $definition,
        $cummulative_definitions
      );

// print '<hr/>';
// print '<hr/>';
// var_dump($definition);
// print '<hr/>';
// var_dump($merged_definition);
// print '<hr/>';
// print '<hr/>';

      if($last == false) {
        return self::apply_a_template($merged_definition, $templates);
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
