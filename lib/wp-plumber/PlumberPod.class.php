<?php

class PlumberPod {

  private $_pod_definition = array();
  private $_pod_data = array();


  function __construct($pod_type, $filters=array()) {
    global $_pod_definition;
    $_pod_definition = array('pod_type' => $pod_type, 'filters' => $filters);
  }


  function __get($attribute) {
    global $_pod_data;

    if(array_key_exists($attribute, $_pod_data)) {
      return $_pod_data[$attribute];
    }
  }


  function __set($attribute, $value) {
    global $_pod_data;

    if(array_key_exists($attribute, $_pod_data)) {
      $_pod_data[$attribute] = $_pod_data;
      return $_pod_data[$attribute];
    }
  }


}

?>
