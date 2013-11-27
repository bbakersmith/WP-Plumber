<?php

class PlumberPod extends ArrayObject {


  protected $_pod_data = array();


  function __construct($pod_type, $filters=false) {
    $this->_pod_data = $this->get_pod_data($pod_type, $filters);
  }


  public function offsetExists($index) {
    return array_key_exists($index, $this->_pod_data);
  }


  public function offsetGet($index) {
    if($this->offsetExists($index)) {
      return $this->_pod_data[$index];
    } else {
      return '';
    }
  }


  public function offsetSet($index, $value) {
    // no setting allowed
  }


  public function offsetUnset($index) {
    // no unsetting allowed
  }


  private function get_pod_data($pod_type, $filters) {
    // get the pod data
    $pods = static::retrieve_pods($pod_type, $filters);

    // check if it's a post type that supports multiple instances
    // (not a settings pod) - and if it is, get all the 
    // TODO possibly other types than post_type, like tags or categories
    if($filters == false && self::get_object_type($pods) == 'post_type') {
      $pods = static::retrieve_pods($pod_type, array()); 
    }

    if(is_string($filters) || self::get_object_type($pods) == 'settings') {
      $results = self::single_pod_fields($pods);
    } else {
      $results = self::multi_pod_fields($pods);
    }

    return $results;
  }


  private function retrieve_pods($pod_type, $filter=false) {
    if($filter == false) { 
      return pods($pod_type);
    } else {
      return pods($pod_type, $filter);
    }
  }


  private function multi_pod_fields($pods) {
    // get all the fields for each of the supplied pods
    $multi_pod_fields = array();
    while($pods->fetch()) {
      $pod_fields = self::single_pod_fields($pods);
      array_push($multi_pod_fields, $pod_fields);
    }
    return $multi_pod_fields;
  }


  private function single_pod_fields($pod) {
    // get all the fields of a single pod
    // get pod metadata
    $basic_fields = $pod->row();

    $convenience_fields = array(
      'permalink' => get_permalink($pod->get_id())
    );
    if(array_key_exists("post_title", $basic_fields)) {
      $convenience_fields['title'] = $basic_fields["post_title"];
    }

    // create nested array from pod fields, for easier 
    // manipulation in render functions and views
    $custom_fields = array();
    foreach($pod->fields() as $field) {
      $field_name = $field['name'];
      $custom_fields[$field_name] = $pod->field($field_name);
    }

    // combine all attributes with precedent given to user fields (CMS)
    $all_fields = array_merge(
      $basic_fields, 
      $convenience_fields, 
      $custom_fields
    );

    return $all_fields;
  }


  private function get_object_type($pods) {
    return $pods->api->pod_data['object_type'];
  }


}

?>
