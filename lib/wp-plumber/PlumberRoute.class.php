<?php

class PlumberRoute {


  public $id, $plumber_definition, $router_definition;


  function __construct($definition) {
    $this->id = $definition['id'];
    $this->plumber_definition = $definition;
    $this->router_definition = self::build_router_definition($definition);
  }


  public function callback($page_arguments=array()) {
print time();
print "ROUTE CALLBACK";
    $query_vars = self::get_query_vars($page_arguments);
print time();
print "ROUTE QUERY ARGS";
  
    // parse and process pods
    $pre_render_args = self::parse_and_process_pods($query_vars);
    $pre_render_args['query_vars'] = $query_vars;
print time();
print "ROUTE PODS";

    $render_args = self::preprocessor($pre_render_args);
print time();
print "ROUTE PREPROC";

    $template = $this->plumber_definition['view_template'];
    render_liquid_template($template, $render_args);
print time();
print "ROUTE RENDER";

    self::postprocessor($render_args);
print time();
print "ROUTE POSTPROC";
  }


  private function get_query_vars($page_arg_vals) {
print time();
print "ARG_VALS";
print_r($page_arg_vals);
print "COUNT ARG VALS";
print count($page_arg_vals);
    if(count($page_arg_vals) > 0) {
      $router_definition = $this->router_definition['page_arguments'];
      $arg_keys = array_slice($router_definition, 1);
print time();
print "ARG_KEYS";
print_r($arg_keys);
      $arg_hash = array_combine($arg_keys, $page_arg_vals);
      return $arg_hash;
    }
    return false;
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
      } else {
        $results_key = false;
      }

      // handle dynamic query vars
      $count = preg_match('/\[([^\]]+)\]/', $pod_string, $query_var_key);
      if($count > 0) {
        $parts = explode('[', $pod_string, 2);
        $pod_string = $parts[0];
        $pod_id_or_slug = $query_vars[$query_var_key[1]];
      }

      // form args
      $results_key = $results_key != false ? $results_key : $pod_string;
      $pod_type = $pod_string;

      // get pods
      if(isset($pod_id_or_slug)) {
        $pods = pods($pod_type, $pod_id_or_slug);
      } else {
        $pods = pods($pod_type);
        // TODO possibly other types than post_type, like tags or categories
        if(self::get_object_type($pods) == 'post_type') {
          // TODO allow pod_templates for filtering
          $pods = pods($pod_type, array()); 
        }
      }

      if(isset($pod_id_or_slug) || 
         self::get_object_type($pods) == 'settings') {

        $results = self::single_pod_fields($pods);
      } else {
        $results = self::multi_pod_fields($pods);
      }

      // add pods to array
      $all_pods[$results_key] = $results;
    }

    return $all_pods;
  }


  private function get_object_type($pods) {
    return $pods->api->pod_data['object_type'];
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


 // get all the fields for each of the supplied pods
  private function multi_pod_fields($pods) {
print "START MULTI POD FIELDS!!!";
    $multi_pod_fields = array();
    while($pods->fetch()) {
      $pod_fields = self::single_pod_fields($pods);
      array_push($multi_pod_fields, $pod_fields);
    }
    return $multi_pod_fields;
  }


  // get all the fields of a single pod
  private function single_pod_fields($pod) {
print "SINGLE POD FIELDS";
    $basic_fields = $pod->row();
    $basic_fields["url"] = get_permalink($pod->id());

    if(array_key_exists("post_title", $basic_fields)) {
      $basic_fields["title"] = $basic_fields["post_title"];
    }

    $custom_fields = array();
    foreach($pod->fields() as $field) {
      $field_name = $field['name'];
      $custom_fields[$field_name] = $pod->field($field_name);
    }

    $all_fields = array_merge(
      $basic_fields, 
      $custom_fields
    );
print_r($all_fields);
    return $all_fields;
  }


}

?>
