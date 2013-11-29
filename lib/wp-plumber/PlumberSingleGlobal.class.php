<?php

class PlumberSingleGlobal {

  // the key for $GLOBALS containing the single active instance of
  // the inheriting class. this is a more flexible singleton, where many
  // instances of the class may exist at once, but a reference to the
  // newest one is stored in a GLOBAL variable, the key to which is 
  // made available to static methods within the inheriting class via the
  // static class variable $global_key


  // this initizing method must be copied to the inheriting
  function __construct($key='') {
    $this->create_single_global_reference($key);
  }


  protected static function get_single_global() {
    return $GLOBALS[self::$global_key];
  }
  

}

?>
