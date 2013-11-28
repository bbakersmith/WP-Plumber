<?php

define('WP_PLUMBER_TEST', true);

require_once('Plumber.php');


class WPRouterStub {
  public function add_route($path, $definition) {}
}


class PlumberTest extends PHPUnit_Framework_TestCase {


  protected function setUp() {
    global $wp_router_stub, 
           $plumber_stub, 
           $wp_route_definitions, 
           $wp_route_templates; 

    $wp_router_stub = $this->getMock('WPRouterStub', array('add_route'));

    $plumber_stub = $this->getMock('Plumber', 
      array('get_all_pod_data', 'render_view_template', 'user_callback')
    );

    $plumber_stub->expects($this->any())
                           ->method('get_all_pod_data')
                           ->with($this->isType('array'))
                           ->will($this->returnValue(array()));
 
    $plumber_stub->expects($this->any())
                           ->method('render_view_template')
                           ->with($this->stringContains('pages/'))
                           ->will($this->returnValue(false));
 
    $wp_route_definitions = array(

      // 0
      '^' => array(
        'view_template' => 'pages/home',
        'pre_render' => 'fake_pre_render',
        'post_render' => 'fake_post_render'
      ),

      // 1
      'contact-us' => array(
        'pods' => array('content:contact_page'),
        'view_template' => 'pages/basic_page'
      ),

      // 2
      'articles' => array(
        'route_vars' => array(
          // can this be an integer or must it be a string due to
          // wp router assuming integers are dynamic variable numbers?
          'page' => 1
        ),
        'route_template' => 'articles_list_page'
      ),

      // 3 
      'articles/{page}' => array(
        'route_template' => 'articles_list_page'
      ),

      // 4
      'article/{id}' => array(
        'pods' => array('content:article{id}'),
        'view_template' => 'pages/articles/single'
      ),

      // 5
      'odd-man' => array(
        'route_template' => 'false'
      ),

      // 6
      'wrong-man' => array(
        'pre_render_fn' => 'notreal',
        'post_render_fn' => 'notreal',
        'view_template' => 'notreal',
        'route_template' => 'notreal'
      ), 

      // 7
      '*' => array(
        'view_template' => 'pages/home'
      )
      
    );


    $set_route_templates = array(

      'default' => array(
        'pods' => array('settings:demo_site_settings')
      ),

      'list_page' => array(
        'pod_filters' => array(
          'list_items' => array(
            'orderby' => 'post_date DESC',    
            'limit' => 3,
            'page' => '{page}'
          )
        )
      ),

      'articles_list_page' => array(
        'pods' => array('list_items:article'),
        'view_template' => 'pages/articles',
        'route_template' => 'list_page'
      )

    );


    $plumber_stub->set_routes($wp_route_definitions);
    $plumber_stub->set_route_templates($wp_route_templates);

    $user_functions_stub_class = $this->getMockClass('UserFunctionsStub',
      array('pre_render', 'render_view', 'post_render')
    );
  }


  // tests


  public function testWPRouterDefinitions() {
    // ensure that proper route definitions are being passed
    // to WP Router
    global $plumber_stub;
    $wp_router_stub = $this->getMock('WPRouterStub', array('add_route'));

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

    $plumber_stub->create_routes($wp_router_stub);
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
// 
//     $local_plumber_stub = $this->getMockClass(
//       'Plumber', 
//       array(
//         'get_all_pod_data', 
//         'render_view_template', 
//         'create_routes_with_factory'
//       )
//     );
// 
//     // contact page inheriting from default route
//     $local_plumber_stub::staticExpects($this->at(1))
//           ->method('create_routes_with_factory')
//           ->with($this->callback(function($def) {
//                    var_dump($def);
//                    return $def['pods'] == array(
//                      'settings:demo_site_settings',
//                      'content:contact_page'
//                    );
//           }))
//           ->will($this->returnValue(array()));
// 
//     // testing route_template multi-inheritance
//     $local_plumber_stub::staticExpects($this->at(3))
//           ->method('create_routes_with_factory')
//           ->with($this->callback(function($def) {
//                    return $def['pod_filters'] == array(
//                      'list_items' => array(
//                        'orderby' => 'post_date DESC',    
//                        'limit' => 3,
//                        'page' => '{page}'
//                      )
//                    ) &&
//                    'view_template' == 'pages/articles' &&
//                    array_key_exists('route_template', $def) == false &&
//                    in_array('settings:demo_site_settings', $def['pods']);
//           }))
//           ->will($this->returnValue(array()));
// 
//     // don't inherit if route_template set to false
//     $local_plumber_stub::staticExpects($this->at(5))
//           ->method('create_routes_with_factory')
//           ->with($this->callback(function($def) {
//                    return in_array(
//                     'settings:demo_site_settings', 
//                     $def['pods'] 
//                    ) == false;
//           }))
//           ->will($this->returnValue(array()));
// 
//     // if specified route template doesn't exist, just remove the 
//     // route_template attribute and return the rest of the definition
//     // as is
//     $local_plumber_stub::staticExpects($this->at(6))
//           ->method('create_routes_with_factory')
//           ->with($this->callback(function($def) {
//                    return array_key_exists('route_template', $def) == false;
//           }))
//           ->will($this->returnValue(array()));
// 
//     $local_plumber_stub::create_routes($wp_router_stub);
// 
  }


  public function testPreAndPostRender() {
    global $plumber_stub;

    $wp_router_stub = $this->getMock('WPRouterStub', array('add_route'));

    // these expectations are super fragile due to at() being based
    // on the order of _all_ method calls made on an object...?
    $plumber_stub->staticExpects($this->at(1))
                                 ->method('user_callback')
                                 ->with($this->equalTo('fake_pre_render'))
                                 ->will($this->returnValue(false));

    $plumber_stub->staticExpects($this->at(3))
                                 ->method('user_callback')
                                 ->with($this->equalTo('fake_post_render'))
                                 ->will($this->returnValue(false));

    $plumber_stub::router_callback(0);
  }


  public function testGettingPodData() {
    // check that pod objects are receiving the correct pod definitions and
    // filters
  }


  public function testViewRenderData() {
    // check that data being passed to the views is correct in structure
    // and content
  }


}

?>
