<?php

define('WP_PLUMBER_TEST', true);

require_once('Plumber.php');


class WPRouterStub {
  public function add_route($path, $definition) {}
}


class UserFunctionStubs {
  protected static $_active_instance;

  public function __construct() {
    static::$_active_instance = $this;
  }

  public static function set_active_instance($instance) {
    self::$_active_instance = $instance;
    return $instance;
  }

  public static function get_active_instance() {
    return self::$_active_instance;
  }

  public static function pre_render($args) {
    // pre_render must return a value in order to modify args
    return self::$_active_instance->singleton_pre_render($args);
  }
  public function singleton_pre_render($args) {print "pre_render($args) called...";}

  public static function view_render($template, $args) {
    self::$_active_instance->singleton_view_render($template, $args);
  }
  public function singleton_view_render($template, $args) {print "view_render($args) called...";}

  public static function post_render($args) {
    self::$_active_instance->singleton_post_render($args);
  }
  public function singleton_post_render($args) {print "post_render($args) called...";}

}


class PlumberTest extends PHPUnit_Framework_TestCase {


  protected function setUp() {
    global $wp_router_stub, 
           $plumber_stub, 
           $wp_route_definitions, 
           $wp_route_templates;

    $wp_router_stub = $this->getMock('WPRouterStub', array('add_route'));

    $plumber_stub = $this->getMock('PlumberInstance', 
      array('get_absolute_views_directory')
    );
 
    $plumber_stub->expects($this->any())
      ->method('render_view_template')
      ->will($this->returnValue(dirname(__FILE__).'/views/')
    );

    $pod_stub_class = $this->getMockClass('PlumberPod',
      array('get_data')
    );
    $plumber_stub->plumber_pod_class = $pod_stub_class;

    Plumber::set_active_instance($plumber_stub);

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
      'multi-inheritance' => array(
        'route_template' => 'simple_template'
      ),

      // 6
      'no-inheritance' => array(
        'route_template' => false
      ),

      // 7
      'wrong-man' => array(
        'pre_render_fn' => 'notreal',
        'post_render_fn' => 'notreal',
        'view_template' => 'notreal',
        'route_template' => 'notreal'
      ), 

      // 8
      '*' => array(
        'view_template' => 'pages/home'
      )
      
    );


    $wp_route_templates = array(

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
      ),

      'simple_template' => array(
        'view_template' => 'pages/simple'
      )

    );


    $wp_route_defaults = array(
      'pods' => array('settings:demo_site_settings'),
      'view_template' => 'pages/default'
    );


    Plumber::set_routes($wp_route_definitions);
    Plumber::set_route_templates($wp_route_templates);
    Plumber::set_route_defaults($wp_route_defaults);
    Plumber::set_view_render('UserFunctionStubs::view_render');
  }


  public function get_user_function_stubs() {
    $user_function_stubs = $this->getMock('UserFunctionStubs',
      array(
        'singleton_pre_render', 
        'singleton_view_render', 
        'singleton_post_render'
      )
    );
    return $user_function_stubs;
  }


  // tests


  public function testPlumberSingleGlobalIsCreated() {
    global $plumber_stub;
    $this->assertEquals(Plumber::get_active_instance(), $plumber_stub);
  }


  public function testUserFunctionStubsSingleGlobalIsCreated() {
    $user_function_stubs = $this->get_user_function_stubs();

    $this->assertEquals(
      UserFunctionStubs::get_active_instance(),
      $user_function_stubs
    );
  }


  public function testWPRouterDefinitions() {
    // ensure that proper route definitions are being passed
    // to WP Router
    $wp_router_stub = $this->getMock('WPRouterStub', array('add_route'));

    // bare minimum homepage test
    $wp_router_stub->expects($this->at(0))
      ->method('add_route')
      ->with(
        $this->equalTo('$'),
        $this->callback(function($def) {
          return $def['query_vars']['plumber_route_id'] == '^' &&
                 $def['page_callback'] == array(
                   'DELETE' => 'Plumber::router_callback_delete',
                   'GET' => 'Plumber::router_callback_get',
                   'POST' => 'Plumber::router_callback_post',
                   'PUT' => 'Plumber::router_callback_put',
                   'default' => 'Plumber::router_callback_get'
                 ) &&
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
               return $def['query_vars']['plumber_route_id'] == 'articles/{page}' &&
                      $def['query_vars']['page'] == 1 &&
                      $def['page_arguments'] == array(
                        'plumber_route_id',
                        'page'
                      );
             }))
      ->will($this->returnValue(null));

    Plumber::create_routes($wp_router_stub);
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
    global $wp_router_stub;

    $local_function_stubs = $this->get_user_function_stubs();

    $local_function_stubs->expects($this->exactly(1))
      ->method('singleton_pre_render')
      ->with($this->callback(function($args) {
        return $args['route_vars'] == array('test_var' => 'test_value');
      }))
      ->will($this->returnValue(false)
    );

    $local_function_stubs->expects($this->exactly(1))
      ->method('singleton_view_render')
      ->with($this->equalTo('pages/home'),
        $this->callback(function($args) {
          return $args['route_vars'] == array('test_var' => 'test_value');
        })
      )
      ->will($this->returnValue(false));

    $local_function_stubs->expects($this->exactly(1))
      ->method('singleton_post_render')
      ->with($this->callback(function($args) {
        return $args['route_vars'] == array('test_var' => 'test_value');
      }))
      ->will($this->returnValue(false)
    );

    UserFunctionStubs::set_active_instance($local_function_stubs);

    Plumber::create_routes($wp_router_stub);
    Plumber::router_callback_get('^');
  }


  public function testPreAndViewAndPostRenderArgsWithModification() {
    global $wp_router_stub;

    $local_function_stubs = $this->get_user_function_stubs();

    $local_function_stubs->expects($this->exactly(1))
      ->method('singleton_pre_render')
      ->with($this->callback(function($args) {
        return $args['route_vars'] == array('test_var' => 'test_value');
      }))
      ->will($this->returnValue(
        array(
          'route_vars' => array(
            'test_var' => 'new_value'
          )
        )
      ));

    $local_function_stubs->expects($this->exactly(1))
      ->method('singleton_view_render')
      ->with($this->equalTo('pages/home'),
        $this->callback(function($args) {
          return $args['route_vars'] == array('test_var' => 'new_value');
        })
      )
      ->will($this->returnValue(false));

    $local_function_stubs->expects($this->exactly(1))
      ->method('singleton_post_render')
      ->with($this->callback(function($args) {
        return $args['route_vars'] == array('test_var' => 'new_value');
      }))
      ->will($this->returnValue(false));

    UserFunctionStubs::set_active_instance($local_function_stubs);

    Plumber::create_routes($wp_router_stub);
    Plumber::router_callback_get('^');
  }


  public function testViewRenderData() {
    // check that data being passed to the views is correct in structure
    // and content
    global $wp_router_stub;

    $pod_factory_stub = $this->getMockBuilder('PlumberPodFactory')
      ->setMethods(array('create_single_pod'))
      ->disableOriginalConstructor()
      ->getMock(); 
    $plumber = Plumber::get_active_instance();
    $plumber->plumber_pod_factory = $pod_factory_stub;

    $local_function_stubs = $this->get_user_function_stubs();
    $local_function_stubs->expects($this->once())
      ->method('singleton_view_render')
      ->with(
        $this->equalTo('pages/articles'),
        $this->callback(function($args) {
          $page = $args['route_vars']['page'];
          $other = $args['route_vars']['something'];
          return $page == 2 && $other == 'else';
        })
      )
      ->will($this->returnValue(false));

    Plumber::set_active_instance($plumber);

    Plumber::create_routes($wp_router_stub);

    UserFunctionStubs::set_active_instance($local_function_stubs);

    // id, page
    Plumber::router_callback_get('articles/{page}', 2);
  }


  public function testParsingPodSelectorsAndAssigningFakeData() {
    // check that pod objects are receiving the correct pod definitions and
    // filters
    global $wp_router_stub;

    $plumber = Plumber::get_active_instance();

    $local_pod_stub_class = $this->getMockClass('PlumberPod',
      array('get_data')
    );

    $local_pod_stub_class::staticExpects($this->exactly(2))
      ->method('get_data')
      ->with(
        $this->logicalOr(
          $this->equalTo('article'), 
          $this->equalTo('demo_site_settings')
        ),
        $this->logicalOr(
          $this->equalTo('a-test-slug'),
          $this->equalTo(false)
        )
      )
      ->will($this->returnCallback(function($type, $filter) {
        if($type == 'article') {
          return array(
            'pod_test_title' => 'A Test Title',
            'pod_test_url' => 'http://test.com'
          );
        } else if($type == 'demo_site_settings') {
          return array(
            'global_title' => 'Global Test Title',
            'global_url' => 'http://test.com'
          );
        } else {
          return null;
        }
      }));

    $plumber->plumber_pod_class = $local_pod_stub_class;

    $local_function_stubs = $this->get_user_function_stubs();
    $local_function_stubs->expects($this->once())
      ->method('singleton_view_render')
      ->with(
        $this->equalTo('pages/articles/single'),
        $this->callback(function($args) {
          return $args['content']['pod_test_title'] == 'A Test Title' && 
                 $args['content']['pod_test_url'] == 'http://test.com' &&
                 $args['settings']['global_title'] == 'Global Test Title' && 
                 $args['settings']['global_url'] == 'http://test.com';
        }))
        ->will($this->returnValue(false)
      );

    UserFunctionStubs::set_active_instance($local_function_stubs);

    Plumber::set_active_instance($plumber);

    Plumber::create_routes($wp_router_stub);
    Plumber::router_callback_get('article/{id}', 'a-test-slug');
  }


  public function testMultiTemplateDefaultInheritance() {
    // check that pod objects are receiving the correct pod definitions and
    // filters
    global $wp_router_stub;

    $plumber = Plumber::get_active_instance();

    $local_pod_stub_class = $this->getMockClass('PlumberPod',
      array('get_data')
    );

    $local_pod_stub_class::staticExpects($this->any())
      ->method('get_data')
      ->with($this->equalTo('demo_site_settings'), false)
      ->will($this->returnValue(array(
        'global_title' => 'Global Test Title',
        'global_url' => 'http://test.com'
      )));

    $local_function_stubs = $this->get_user_function_stubs();
    $local_function_stubs->expects($this->once())
      ->method('singleton_view_render')
      ->with(
        $this->equalTo('pages/simple'),
        $this->callback(function($args) {
          return $args['settings']['global_title'] == 'Global Test Title' && 
                 $args['settings']['global_url'] == 'http://test.com';
        }))
        ->will($this->returnValue(false)
      );

    UserFunctionStubs::set_active_instance($local_function_stubs);

    $plumber->plumber_pod_class = $local_pod_stub_class;

    Plumber::set_active_instance($plumber);

    Plumber::create_routes($wp_router_stub);
    Plumber::router_callback_get('multi-inheritance', 'a-test-slug');
  }


  public function testMultiTemplateDefaultInheritanceOverride() {
    // check that pod objects are receiving the correct pod definitions and
    // filters
    global $wp_router_stub;

    $plumber = Plumber::get_active_instance();

    $local_pod_stub_class = $this->getMockClass('PlumberPod',
      array('get_data')
    );

    $local_pod_stub_class::staticExpects($this->never())->method('get_data');

    $local_function_stubs = $this->get_user_function_stubs();
    $local_function_stubs->expects($this->never())
      ->method('singleton_view_render');

    UserFunctionStubs::set_active_instance($local_function_stubs);

    $plumber->plumber_pod_class = $local_pod_stub_class;

    Plumber::set_active_instance($plumber);

    Plumber::create_routes($wp_router_stub);
    Plumber::router_callback_get('no-inheritance');
  }


}

?>
