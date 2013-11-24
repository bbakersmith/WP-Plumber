<?php

// a bare interface for pods() to accomodate mocking for testing

class PlumberPodsInterface {

  public static function get($pod_type, $filter=false) {
    if($filter == false) { 
      return pods($pod_type);
    } else {
      return pods($pod_type, $filter);
    }
  }

}

?>
