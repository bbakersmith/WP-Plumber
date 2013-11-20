<?php

class PlumberRouteFactory {


  public static function create_routes($definitions) {
    $new_routes = array();

    $rank = 0;
    foreach($definitions as $path => $definition) {
      $definition['id'] = $rank;
      array_push($new_routes, self::create_route_object($path, $definition));
      $rank++;
    }

    return $new_routes;
  }


  public static function create_aliases($aliases) {
    $new_route_definitions = array();

    if(count($aliases) > 0) {
      foreach($aliases as $path => $destination) {
        $definition = PlumberSpecialRoutes::redirect($destination);
        $new_route_definitions[$path] = $definition;
      }
    }
var_dump($new_route_definitions);

    return self::create_routes($new_route_definitions);
  }


  private static function create_route_object($path, $definition) {
    $definition['path'] = $path;
    return new PlumberRoute($definition);
  }


}

?>
