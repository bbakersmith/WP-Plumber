<?php

class Plumber {


  protected static $_active_instance;


  public function __construct() {}


  public static function __callStatic($method, $args) {
    $active = static::get_active_instance();
    return call_user_func_array(array($active, $method), $args);
  }


  public static function set_active_instance($instance) {
    self::$_active_instance = $instance;
    return $instance;
  }


  public static function get_active_instance() {
    return self::$_active_instance;
  }


}

?>
