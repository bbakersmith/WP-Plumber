<?php

require_once('lib/wp-plumber/Plumber.php');

class PlumberTest extends PHPUnit_Framework_TestCase {

  protected function setUp() {

    $this->routes = array(

      '' => array(
        'pods' => array('home_settings'),
        'route_template' => 'second'
      ),

      'basic' => array(
        'pods' => array('home_settings')
      ),

      'variable/:first/:second' => array(
        'pods' => array('name:thing[second]'),
        'view_template' => 'things/single'
      )

    );

    $this->route_templates = array(
      'second' => array(
        'view_template' => 'second',
        'route_template' => 'third'
      ),

      'third' => array(
        'view_template' => 'third',
        'pods' => array('global_settings')
      )
    );

    Plumber::register_routes(array(
      'routes' => $this->routes,
      'route_templates' => $this->route_templates
    ));
  }


  // tests


  public function testBasicRouteInitilization() {
    $wp_plumber_routes = $GLOBALS['wp_plumber_routes'];

    $this->assertEquals(
      $this->routes['basic']['pods'],
      $wp_plumber_routes[1]->plumber_definition['pods']
    );
  }


  public function testRouteTemplateInheritance() {
    $wp_plumber_routes = $GLOBALS['wp_plumber_routes'];

    $target_definition = $wp_plumber_routes[0]->plumber_definition;

    $this->assertEquals('second', $target_definition['view_template']);
    $this->assertContains('global_settings', $target_definition['pods']);
    $this->assertContains('home_settings', $target_definition['pods']);
  }


  public function testWPRouterDefinitionGeneration() {
    $definitions = Plumber::generate_route_definitions();
    $target_definition = $definitions[2];

    $expected_path = '^variable/([^\/\s]+)/([^\/\s]+)$';

    $this->assertEquals($expected_path, $target_definition['path']);
    $this->assertEquals(false, $target_definition['template']); 

    $this->assertEquals(1, $target_definition['query_vars']['first']);
    $this->assertEquals(2, $target_definition['query_vars']['second']);

    $this->assertEquals('first', $target_definition['page_arguments'][1]);
    $this->assertEquals('second', $target_definition['page_arguments'][2]);
  }


  public function testRouteCallback() {
    $id = 2;
    $callback = Plumber::callback($id, 'firstval', 'secondval');
    print "route: ";
    print_r($GLOBALS['wp_plumber_routes'][$id]);
    print "callback: ";
    print_r($callback);
  }

  public function testPodArgumentFormation() {

  }

  public function testArgArrayFormation() {

  }

  public function testPreprocessorCalling() {

  }

}

?>
