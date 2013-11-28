<?php

class PlumberSingleGlobal {

  // the key for $GLOBALS containing the single active instance of
  // the inheriting class. this is a more flexible singleton, where many
  // instances of the class may exist at once, but a reference to the
  // newest one is stored in a GLOBAL variable, the key to which is 
  // made available to static methods within the inheriting class via the
  // static class variable $global_key

  protected static $global_key = '';


  public function __construct($key='') {
    $this->create_single_global_reference($key);
  }


  protected function create_single_global_reference($key='') {
    if($key != '') {
      self::$global_key = $key;
    } 

    if(self::$global_key != '') {
      $GLOBALS[self::$global_key] =& $this;
    } else {
     throw new Exception('No global key provided, cannot create active reference. Global key provided:'.self::$global_key);
    }
  }
  

}

?>
