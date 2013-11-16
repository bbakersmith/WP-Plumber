<?php

require_once('lib/wp-plumber/Plumber.php');


class PlumberTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {

    $this->routes = array(
      '^basic$' => array(
        'pods' => array('home_settings')
      ),

      '$' => array(
        'pods' => array('home_settings'),
        'route_template' => 'second'
      )
    );

    $this->route_templates = array(
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
      'routes' => $this->routes,
      'route_templates' => $this->route_templates
    ));

  }


  // tests


  public function testBasicRouteInitilization() {
    global $wp_plumber_routes;

    $basic_key = '^basic$';

    $this->assertEquals(
      $this->routes[$basic_key], 
      $wp_plumber_routes[$basic_key]
    );
  }


  public function testRouteTemplateInheritance() {
    global $wp_plumber_routes;

    $this->assertEquals('second', $wp_plumber_routes['$']['views_template']);
    $this->assertContains('global_settings', $wp_plumber_routes['$']['pods']);
    $this->assertContains('home_settings', $wp_plumber_routes['$']['pods']);
  }


  public function testWPRouterDefinitionGeneration() {
    $definitions = Plumber::generate_route_definitions();

    $this->assertEquals('$', $definitions['path']);
    $this->assertEquals(false, $definitions['template']);

    $this->assertContains('1', $definitions['query_vars']);
    $this->assertContains('2', $definitions['query_vars']);

    $this->assertContains('1', $definitions['page_arguments']);
    $this->assertContains('2', $definitions['page_arguments']);
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
