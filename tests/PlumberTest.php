<?php

require_once('Plumber.php');
require_once('tests/functions.php');


class WPRouterStub {
  public function add_route($path, $definition) {}
}

class WPPodsStub {
  public function pods($pod_type, $filter=false) {}
}


class PlumberTest extends PHPUnit_Framework_TestCase {


  protected function setUp() {
    global $wp_router_stub, $wp_plumber_stub;

    $wp_router_stub = $this->getMock('WPRouterStub');
    $wp_router_stub->expects($this->any())
          ->method('add_route')
          ->with(2) // parameters that are expected
          ->will($this->returnValue('foo'));

    $wp_plumber_stub = $this->getMock('Plumber');
    $wp_plumber_stub::staticExpects($this->any())
          ->method('get_all_pod_data')
          ->with(3)
          ->will($this->returnValue('burp'));
  }


  // tests


  public function testRouteDefinitions() {
    // ensure that proper route definitions are being passed
    // to WP Router
    global $wp_router_stub, $wp_plumber_stub;

    $wp_plumber_stub::create_routes($wp_router_stub);
  }


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


  // public function testWPRouterDefinitionGeneration() {
  //   $definitions = Plumber::generate_route_definitions();
  //   $target_definition = $definitions[2];

  //   $expected_path = '^variable/([^\/\s]+)/([^\/\s]+)$';

  //   $this->assertEquals($expected_path, $target_definition['path']);
  //   $this->assertEquals(false, $target_definition['template']); 

  //   $this->assertEquals(1, $target_definition['query_vars']['first']);
  //   $this->assertEquals(2, $target_definition['query_vars']['second']);

  //   $this->assertEquals('first', $target_definition['page_arguments'][1]);
  //   $this->assertEquals('second', $target_definition['page_arguments'][2]);
  // }


  public function testRouteCallback() {
    $id = 2;
    $callback = Plumber::router_callback($id, 'firstval', 'secondval');
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
