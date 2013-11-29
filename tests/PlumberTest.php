<?php

define('WP_PLUMBER_TEST', true);

require_once('Plumber.php');


class WPRouterStub {
  public function add_route($path, $definition) {}
}


class UserFunctionStubs extends PlumberSingleGlobal {
  protected static $global_key = 'wp_plumber_user_functions';

  public function __construct() {
    parent::__construct(self::$global_key);
  }
}


class PlumberTest extends PHPUnit_Framework_TestCase {


  protected function setUp() {
    global $wp_router_stub, 
           $plumber_stub, 
           $wp_route_definitions, 
           $wp_route_templates;

    $wp_router_stub = $this->getMock('WPRouterStub', array('add_route'));

    $plumber_stub = $this->getMock('Plumber', 
      array('get_all_pod_data', 'get_absolute_views_directory')
    );
 
    $plumber_stub->expects($this->any())
      ->method('render_view_template')
      ->will($this->returnValue(dirname(__FILE__).'/views/')
    );

    $wp_route_definitions = array(

      // 0
      '^' => array(
        'view_template' => 'pages/home',
        'route_vars' => array(
          'test_var' => 'test_value'
        ),
        'pre_render' => 'UserFunctionStubs::pre_render',
        'post_render' => 'UserFunctionStubs::post_render'
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
        'route_vars' => array('something' => 'else'),
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
    $plumber_stub->set_view_render('UserFunctionStubs::view_render');
  }


  public function get_user_function_stubs() {
    $user_function_stubs = $this->getMock('UserFunctionStubs',
      array('pre_render', 'view_render', 'post_render')
    );
    return $user_function_stubs;
  }


  // tests


  public function testPlumberSingleGlobalIsCreated() {
    global $plumber_stub;
    $this->assertEquals($GLOBALS['wp_plumber'], $plumber_stub);
  }


  public function testUserFunctionStubsSingleGlobalIsCreated() {
    $user_function_stubs = $this->get_user_function_stubs();

    $this->assertEquals(
      $GLOBALS['wp_plumber_user_functions'], 
      $user_function_stubs
    );
  }


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


  public function testPreAndViewAndPostRenderArgs() {
    global $wp_router_stub, $plumber_stub;

    $local_function_stubs = $this->get_user_function_stubs();

    $local_function_stubs->expects($this->exactly(1))
      ->method('pre_render')
      ->with($this->equalTo(
        array(
          'route_vars' => array(
            'test_var' => 'test_value'
          )
        )
      ))
      ->will($this->returnValue(false)
    );

    $local_function_stubs->expects($this->exactly(1))
      ->method('view_render')
      ->with($this->equalTo('pages/home'),
        $this->equalTo(array(
          'route_vars' => array(
            'test_var' => 'test_value'
          )
        ))
      )
      ->will($this->returnValue(false));

    $local_function_stubs->expects($this->exactly(1))
      ->method('post_render')
      ->with($this->equalTo(
        array(
          'route_vars' => array(
            'test_var' => 'test_value'
          )
        )
      ))
      ->will($this->returnValue(false)
    );

    $GLOBALS['wp_plumber_user_functions'] = $local_function_stubs;

    Plumber::create_routes($wp_router_stub);
    Plumber::router_callback(0);
  }


  public function testPreAndViewAndPostRenderArgsWithModification() {
    global $wp_router_stub, $plumber_stub;

    $local_function_stubs = $this->get_user_function_stubs();

    $local_function_stubs->expects($this->exactly(1))
      ->method('pre_render')
      ->with($this->equalTo(
        array(
          'route_vars' => array(
            'test_var' => 'test_value'
          )
        )
      ))
      ->will($this->returnValue(
        array(
          'route_vars' => array(
            'test_var' => 'new_value'
          )
        )
      ));

    $local_function_stubs->expects($this->exactly(1))
      ->method('view_render')
      ->with($this->equalTo('pages/home'),
        $this->equalTo(array(
          'route_vars' => array(
            'test_var' => 'new_value'
          )
        ))
      )
      ->will($this->returnValue(false));

    $local_function_stubs->expects($this->exactly(1))
      ->method('post_render')
      ->with($this->equalTo(
        array(
          'route_vars' => array(
            'test_var' => 'new_value'
          )
        )
      ))
      ->will($this->returnValue(false));

    $GLOBALS['wp_plumber_user_functions'] = $local_function_stubs;

    Plumber::create_routes($wp_router_stub);
    Plumber::router_callback(0);
  }


  public function testViewRenderData() {
    // check that data being passed to the views is correct in structure
    // and content
    global $wp_router_stub, $plumber_stub;

    $local_function_stubs = $this->get_user_function_stubs();

    $local_function_stubs->expects($this->atLeastOnce())
      ->method('view_render')
      ->with(
       $this->equalTo('pages/articlesm'),
       $this->callback(function($args) {
           $real_args = $args[0];
           $page = $real_args['route_vars']['page'];
           $other = $real_args['route_vars']['something'];
           return $page == 2 && $other == 'elsef';
         })
       )
       ->will($this->returnValue(false));

    Plumber::create_routes($wp_router_stub);

    $GLOBALS['wp_plumber_user_functions'] = $local_function_stubs;

    // id, page
    Plumber::router_callback(3, 2);
  }


  public function testGettingPodData() {
    // check that pod objects are receiving the correct pod definitions and
    // filters
    global $wp_router_stub, $plumber_stub;

    $local_function_stubs = $this->get_user_function_stubs();
  }


}

?>
