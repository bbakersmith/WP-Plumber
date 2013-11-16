<?php

require_once('lib/wp-plumber/Plumber.php');


class PlumberTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {

  }


  // tests


  public function testBasicRouteInitilization() {
    global $wp_plumber_routes;

    $routes = array(
      '$' => array(
        'pods' => array('home_settings'),
        'view_template' => 'pages/home'
      ),
    );

    Plumber::initialize_routes(array(
      'routes' => $routes
    ));

    $this->assertEquals($routes, $wp_plumber_routes);
  }


  public function testRouteTemplateInheritance() {
    global $wp_plumber_routes;

    $routes = array(
      '$' => array(
        'pods' => array('home_settings'),
        'route_template' => 'second'
      ),
    );

    $route_templates = array(
      'second' => array(
        'views_template' => 'second',
        'route_template' => 'third'
      ),

      'third' => array(
        'views_template' => 'third',
        'pods' => array('global_settings')
      )
    );

    Plumber::initialize_routes(array(
      'routes' => $routes,
      'route_templates' => $route_templates
    ));

    $this->assertEquals('second', $wp_plumber_routes['$']['views_template']);
    $this->assertContains('global_settings', $wp_plumber_routes['$']['pods']);
    $this->assertContains('home_settings', $wp_plumber_routes['$']['pods']);
  }


  public function testRouteCallback() {

  }

  public function testPodArgumentFormation() {

  }

  public function testArgArrayFormation() {

  }

  public function testPreprocessorCalling() {

  }

}

?>
