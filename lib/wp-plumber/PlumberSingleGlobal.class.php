<?php

class PlumberSingleGlobal {

  // the key for $GLOBALS containing the single active instance of
  // the inheriting class. this is a more flexible singleton, where many
  // instances of the class may exist at once, but a reference to the
  // newest one is stored in a GLOBAL variable, the key to which is 
  // made available to static methods within the inheriting class via the
  // static class variable $global_key

  protected static $global_key = '';


  // this initizing method must be copied to the inheriting
  function __construct($key='') {
    $this->create_single_global_reference($key);
  }


  public static function __callStatic($method, $args) {
    // allows public non-static methods of the currently active
    // instance to be called as static methods on the class
    //
    // ex. Example::my_method($args);
    //
    //     $example = new Example();
    //     $example->my_method($args);
    //

    // get the currently active instance
    $active_instance = self::get_single_global();

    // call the given method on that instance
    return call_user_func_array(array($active_instance, $method), $args);
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


  protected static function get_single_global() {
    return $GLOBALS[self::$global_key];
  }
  

}

?>
