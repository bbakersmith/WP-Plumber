<?php

class PlumberPods {




  public static function get($pod_strings, $pod_filters, $query_vars) {
    // pods definition syntax
    //
    // thing                  all pods of the given type
    // something:thing        all pods of the given type with named var
    // thing[var]             single pod with query_var (match id or slug)
    //
    $all_pods = array();

    if(count($pod_filters) > 0) {
      $pod_filters = self::apply_nested_query_vars($pod_filters, $query_vars);
    }

    foreach($pod_strings as $k => $pod_string) {
      // handle dynamic query vars
      $query_var_key = self::get_query_var_key($pod_string);
      if($query_var_key != false) {
        $parts = explode('[', $pod_string, 2);
        $pod_string = $parts[0];
        $pod_id_or_slug = $query_vars[$query_var_key];
      }

      // handle pod selector names
      if(preg_match('/:/', $pod_string)) {
        $parts = explode(':', $pod_string, 2);
        $results_key = $parts[0];
        $pod_string = $parts[1];
      } else {
        $results_key = $pod_string;
      }
      $pod_type = $pod_string;

      // filter by id, slug, or pod_filter
      if(isset($pod_id_or_slug)) {
        $filter_by = $pod_id_or_slug;
      } else if(array_key_exists($results_key, $pod_filters)) {
        $filter_by = $pod_filters[$results_key];
      } else {
        // get single pod or all pods, depending on type
        $filter_by = false;
      }

      // get the pod data
      $pods = static::get_pods($pod_type, $filter_by);

      // check if it's a post type that supports multiple instances
      // (not a settings pod) - and if it is, get all the 
      // TODO possibly other types than post_type, like tags or categories
      if($filter_by == false && self::get_object_type($pods) == 'post_type') {
        $pods = static::get_pods($pod_type, array()); 
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
}

?>
