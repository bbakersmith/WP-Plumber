<?php

class Plumber {


  // The Plumber class provides a static public interface to WP Plumber.
  // 
  // The actual data and functionality for the current Plumber instance is
  // represented by a separate class. The Plumber interface stores a
  // single instance of the instance class and relays static calls 
  // made to Plumber (ex. Plumber::set_routes) to the current instance.
  //
  // This primarily enables testing.


  protected static $instance_class = 'PlumberInstance';

  protected static $active_instance = null;


  public function __construct() {}


  public static function __callStatic($method, $args) {
    $active = static::get_active_instance();
    return call_user_func_array(array($active, $method), $args);
  }


  public static function init() {
    return self::set_active_instance(new self::$instance_class);
  }


  public static function set_active_instance($instance) {
    self::$active_instance = $instance;
    return $instance;
  }


  public static function get_active_instance() {
    $active = self::$active_instance;
    if($active == false) {
      $active = static::init();
    }
    return $active;
  }


}

?>
