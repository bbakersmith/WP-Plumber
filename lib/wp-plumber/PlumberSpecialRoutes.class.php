<?php

class PlumberSpecialRoutes {


  public static function redirect($destination) {
    $definition = array(
      'pre_render_fn' => 'PlumberSpecialRoutes::redirect_callback',
      'route_vars' => array('destination' => $destination)
    );
    return $definition;
  }


  public static function redirect_callback($args) {
print "FUCK YEAH";
    $url = site_url('/'.$args['destination']);

    header('Location: ' . $url, true, 302);
    // die();
  }


}

?>
