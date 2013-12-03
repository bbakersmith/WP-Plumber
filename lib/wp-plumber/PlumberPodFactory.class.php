<?php

class PlumberPodFactory {


  const QUERY_VAR_REGEX = '/\{([^\]]+)\}/';

  protected $_class_to_create;

  
  function __construct($class) {
    // assign the class that is to be created by this factory
    $this->_class_to_create = $class;
  }  


  public function create_pods($pod_strings, $pod_filters, $query_vars) {
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
        $parts = explode('{', $pod_string, 2);
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

      // add pod(s) to array
      $all_pods[$results_key] = (array) $this->create_single_pod($pod_type, $filter_by);
    }

    return $all_pods;
  }


  protected function create_single_pod($pod_type, $filter_by) {
    // instead of creating an instance, which was tricky to make array-like,
    // call a static function that instantiates a pod long enough to get
    // an array of its data.
    //
    // this is fairly ugly, but made testing easier.. if pod class could be
    // made to behave more completely like an array (or support simple
    // casting to array) this would not be necessary.

    $class = $this->_class_to_create;
    return $class::get_data($pod_type, $filter_by);
  }


  private function get_query_var_key($string) {
    $count = preg_match(self::QUERY_VAR_REGEX, $string, $query_var_key);
    if($count > 0) {
      return $query_var_key[1];
    } else {
      return false;
    }
  }


  private function apply_nested_query_vars($target, $query_vars) {
    $applied_items = array();
    foreach($target as $key => $value) {
      if(is_string($value)) {
        $applied_items[$key] = self::apply_query_vars($value, $query_vars);
      } else if(is_array($value)) {
        $applied_items[$key] = self::apply_nested_query_vars($value, $query_vars);
      } else {
        $applied_items[$key] = $value;
      }
    }
    return $applied_items;
  }


  private function apply_query_vars($value, $query_vars) {
    $count = preg_match(self::QUERY_VAR_REGEX, $value, $match);
    if($count > 0) {
      $full_match = $match[0];
      $query_var_key = $match[1];
      $new_val = str_replace($full_match, $query_vars[$query_var_key], $value);
      return $new_val;
    }
    return $value;
  }


}

?>
