<?php

class PlumberPods {


  const QUERY_VAR_REGEX = '/\{([^\]]+)\}/';


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

      // handle alternate args keys
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
      }

      // get pods by filter (slug, id, or array of args) or all
      if(isset($filter_by)) {
        $pods = static::get_pods($pod_type, $filter_by);
      } else {
        $pods = static::get_pods($pod_type);
        // TODO possibly other types than post_type, like tags or categories
        if(self::get_object_type($pods) == 'post_type') {
          $pods = static::get_pods($pod_type, array()); 
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


  public static function get_pods($pod_type, $filter=false) {
    if($filter == false) { 
      return pods($pod_type);
    } else {
      return pods($pod_type, $filter);
    }
  }


 // get all the fields for each of the supplied pods
  private static function multi_pod_fields($pods) {
    $multi_pod_fields = array();
    while($pods->fetch()) {
      $pod_fields = self::single_pod_fields($pods);
      array_push($multi_pod_fields, $pod_fields);
    }
    return $multi_pod_fields;
  }


  // get all the fields of a single pod
  private static function single_pod_fields($pod) {
    $basic_fields = $pod->row();
    $basic_fields["permalink"] = get_permalink($pod->id());

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

    return $all_fields;
  }


  private static function get_object_type($pods) {
    return $pods->api->pod_data['object_type'];
  }


  private static function get_query_var_key($string) {
    $count = preg_match(self::QUERY_VAR_REGEX, $string, $query_var_key);
    if($count > 0) {
      return $query_var_key[1];
    } else {
      return false;
    }
  }


  private static function apply_nested_query_vars($target, $query_vars) {
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


  private static function apply_query_vars($value, $query_vars) {
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
