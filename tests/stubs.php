<?php

class WPRouterStub {
  public function add_route($path, $definition) {}
}


class UserFunctionStubs {
  protected static $_active_instance;

  public function __construct() {
    static::$_active_instance = $this;
  }

  public static function set_active_instance($instance) {
    self::$_active_instance = $instance;
    return $instance;
  }

  public static function get_active_instance() {
    return self::$_active_instance;
  }

  public static function pre_render($args) {
    // pre_render must return a value in order to modify args
    return self::$_active_instance->singleton_pre_render($args);
  }
  public function singleton_pre_render($args) {print "pre_render($args) called...";}

  public static function view_render($template, $args) {
    self::$_active_instance->singleton_view_render($template, $args);
  }
  public function singleton_view_render($template, $args) {print "view_render($args) called...";}

  public static function post_render($args) {
    self::$_active_instance->singleton_post_render($args);
  }
  public function singleton_post_render($args) {print "post_render($args) called...";}

}

?>
