<?php

class PlumberRouteCallback {


  public static function call() {
    $args = func_get_args();
    print_r($args);
  }
  

  private static function theme_dir($dirname) {
    return get_stylesheet_directory().'/'.$dirname.'/';
  }


  // callback fetches specified pods

  // combines pods with url_args and additional_args

  // passes combined args (with pods) to preprocessor function

  // passes preprocessor results to view template


}

?>
