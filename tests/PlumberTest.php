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

    $wp_router_stub = $this->getMock('WPRouterStub', array('add_route'));

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


  public function testWPRouterDefinitions() {
    // ensure that proper route definitions are being passed
    // to WP Router
    global $wp_router_stub, $wp_plumber_stub;

    // bare minimum homepage test
    $wp_router_stub->expects($this->at(0))
          ->method('add_route')
          ->with($this->equalTo('$'),
                 $this->callback(function($def) {
                   return $def['query_vars']['plumber_route_id'] == 0 &&
                          $def['page_callback'] == 'Plumber::router_callback' &&
                          $def['template'] == false;
                 }))
          ->will($this->returnValue(null));

    // test basic page route path formation
    $wp_router_stub->expects($this->at(1))
          ->method('add_route')
          ->with($this->equalTo('^contact-us$'), // basic route formation
                 $this->callback(function($def) {
                   return $def['path'] == '^contact-us$';
                 }))

          ->will($this->returnValue(null));

    // test dynamic route vars for proper path formation and argument
    // assignment
    $wp_router_stub->expects($this->at(3))
          ->method('add_route')
          ->with($this->equalTo('^articles/(.*)$'),
                 $this->callback(function($def) {
                   return $def['query_vars']['plumber_route_id'] == 3 &&
                          $def['query_vars']['page'] == 1 &&
                          $def['page_arguments'] == array(
                            'plumber_route_id',
                            'page'
                          );
                 }))
          ->will($this->returnValue(null));

    $wp_plumber_stub::create_routes($wp_router_stub);
  }


  public function testRouteDefinitions() {
    global $wp_router_stub;

    // TODO many of the tests below are actually intended to test 
    // the state of the routes AFTER create_routes_with_factory
    // has been called. Mocking the create_routes_with_factory method
    // is the wrong approach, and I will need to get access to each
    // route for testing another way

    // To this end I've made get_wp_router_definitions protected and
    // had it take the routes as an arg. By mocking this method and
    // checking its method argument the Plumber::$routes will be testable
    // without having to expose $routes with a public accessor. It also
    // is a better approach to get_wp_router_definitions because it
    // decouples it from the internal state of the Plumber static class.

    $local_wp_plumber_stub = $this->getMockClass(
      'Plumber', 
      array(
        'get_all_pod_data', 
        'render_view_template', 
        'create_routes_with_factory'
      )
    );

    // contact page inheriting from default route
    $local_wp_plumber_stub::staticExpects($this->at(1))
          ->method('create_routes_with_factory')
          ->with($this->callback(function($def) {
                   var_dump($def);
                   return $def['pods'] == array(
                     'settings:demo_site_settings',
                     'content:contact_page'
                   );
          }))
          ->will($this->returnValue(array()));

    // testing route_template multi-inheritance
    $local_wp_plumber_stub::staticExpects($this->at(3))
          ->method('create_routes_with_factory')
          ->with($this->callback(function($def) {
                   return $def['pod_filters'] == array(
                     'list_items' => array(
                       'orderby' => 'post_date DESC',    
                       'limit' => 3,
                       'page' => '{page}'
                     )
                   ) &&
                   'view_template' == 'pages/articles' &&
                   array_key_exists('route_template', $def) == false &&
                   in_array('settings:demo_site_settings', $def['pods']);
          }))
          ->will($this->returnValue(array()));

    // don't inherit if route_template set to false
    $local_wp_plumber_stub::staticExpects($this->at(5))
          ->method('create_routes_with_factory')
          ->with($this->callback(function($def) {
                   return in_array(
                    'settings:demo_site_settings', 
                    $def['pods'] 
                   ) == false;
          }))
          ->will($this->returnValue(array()));

    // if route template doesn't exist, don't fail and don't leave it in def
    $local_wp_plumber_stub::staticExpects($this->at(6))
          ->method('create_routes_with_factory')
          ->with($this->callback(function($def) {
                   return array_key_exists('route_template', $def) == false;
          }))
          ->will($this->returnValue(array()));

    $local_wp_plumber_stub::create_routes($wp_router_stub);
  }


}

?>
