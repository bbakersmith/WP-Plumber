<?php

define('WP_PLUMBER_TEST', true);

require_once('Plumber.php');
require_once('tests/functions.php');


class WPRouterStub {
  public function add_route($path, $definition) {}
}


class PlumberTest extends PHPUnit_Framework_TestCase {


  protected function setUp() {
    global $wp_router_stub, $wp_plumber_stub;

    $wp_router_stub = $this->getMock('WPRouterStub');
    $wp_router_stub->expects($this->any())
          ->method('add_route')
          ->with($this->stringEndsWith('$'),
                 $this->arrayHasKey('page_callback'))
          ->will($this->returnValue('foo'));

    $wp_plumber_stub = $this->getMockClass(
      'Plumber', 
      array('get_all_pod_data', 'render_view_template')
    );
    $wp_plumber_stub::staticExpects($this->any())
          ->method('get_all_pod_data')
          ->with($this->isType('array'))
          ->will($this->returnValue(array()));
    $wp_plumber_stub::staticExpects($this->any())
          ->method('render_view_template')
          ->with($this->stringContains('pages/'))
          ->will($this->returnValue(false));
  }


  // tests


  public function testRouteDefinitions() {
    // ensure that proper route definitions are being passed
    // to WP Router
    global $wp_router_stub, $wp_plumber_stub;

    $wp_plumber_stub::create_routes($wp_router_stub);
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


}

?>
