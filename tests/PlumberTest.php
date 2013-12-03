<?php

define('WP_PLUMBER_TEST', true);

require_once('Plumber.php');

require_once(dirname(__FILE__).'/stubs.php');


class PlumberStaticTest extends PHPUnit_Framework_TestCase {


  protected function setUp() {
    global $wp_router_stub, 
           $plumber, 
           $wp_route_definitions, 
           $wp_route_templates;

    include(dirname(__FILE__).'/functions.php');

    $plumber->expects($this->any())
      ->method('render_view_template')
      ->will($this->returnValue(dirname(__FILE__).'/views/')
    );

    $pod_stub_class = $this->getMockClass('PlumberPod',
      array('get_data')
    );
    $plumber->plumber_pod_class = $pod_stub_class;

    PlumberStatic::set_active_instance($plumber);

    $wp_router_stub = $this->getMock('WPRouterStub', array('add_route'));
  }


  // helpers


  public function get_user_function_stubs() {
    $user_function_stubs = $this->getMock('UserFunctionStubs',
      array(
        'singleton_pre_render', 
        'singleton_view_render', 
        'singleton_post_render',
        'singleton_another_render',
        'singleton_final_render'
      )
    );
    return $user_function_stubs;
  }


  // tests


  public function testPlumberStaticSingleGlobalIsCreated() {
    global $plumber;
    $this->assertEquals(PlumberStatic::get_active_instance(), $plumber);
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
                   'DELETE' => 'PlumberStatic::router_callback_delete',
                   'GET' => 'PlumberStatic::router_callback_get',
                   'POST' => 'PlumberStatic::router_callback_post',
                   'PUT' => 'PlumberStatic::router_callback_put',
                   'default' => 'PlumberStatic::router_callback_get'
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

    PlumberStatic::create_routes($wp_router_stub);
  }


  public function testRouteDefinitions() {
    global $wp_router_stub;
//     // if specified route template doesn't exist, just remove the 
//     // route_template attribute and return the rest of the definition
//     // as is
//     $local_plumber::staticExpects($this->at(6))
//           ->method('create_routes_with_factory')
//           ->with($this->callback(function($def) {
//                    return array_key_exists('route_template', $def) == false;
//           }))
//           ->will($this->returnValue(array()));
// 
//     $local_plumber::create_routes($wp_router_stub);
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

    PlumberStatic::create_routes($wp_router_stub);
    PlumberStatic::router_callback_get('^');
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

    PlumberStatic::create_routes($wp_router_stub);
    PlumberStatic::router_callback_get('^');
  }


  public function testViewRenderData() {
    // check that data being passed to the views is correct in structure
    // and content
    global $wp_router_stub;

    $pod_factory_stub = $this->getMockBuilder('PlumberPodFactory')
      ->setMethods(array('create_single_pod'))
      ->disableOriginalConstructor()
      ->getMock(); 
    $plumber = PlumberStatic::get_active_instance();
    $plumber->plumber_pod_factory = $pod_factory_stub;

    $local_function_stubs = $this->get_user_function_stubs();
    $local_function_stubs->expects($this->exactly(1))
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

    PlumberStatic::set_active_instance($plumber);

    PlumberStatic::create_routes($wp_router_stub);

    UserFunctionStubs::set_active_instance($local_function_stubs);

    // id, page
    PlumberStatic::router_callback_get('articles/{page}', 2);
  }


  public function testParsingPodSelectorsAndAssigningFakeData() {
    // check that pod objects are receiving the correct pod definitions and
    // filters
    global $wp_router_stub;

    $plumber = PlumberStatic::get_active_instance();

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

    PlumberStatic::set_active_instance($plumber);

    PlumberStatic::create_routes($wp_router_stub);
    PlumberStatic::router_callback_get('article/{id}', 'a-test-slug');
  }


  public function testMultiTemplateDefaultInheritance() {
    // check that pod objects are receiving the correct pod definitions and
    // filters
    global $wp_router_stub;

    $plumber = PlumberStatic::get_active_instance();

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
    $local_function_stubs->expects($this->exactly(1))
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

    PlumberStatic::set_active_instance($plumber);

    PlumberStatic::create_routes($wp_router_stub);

    PlumberStatic::router_callback_get('multi-inheritance', 'a-test-slug');
  }


  public function testMultiTemplateDefaultInheritanceOverride() {
    // check that pod objects are receiving the correct pod definitions and
    // filters
    global $wp_router_stub;

    $plumber = PlumberStatic::get_active_instance();

    $local_pod_stub_class = $this->getMockClass('PlumberPod',
      array('get_data')
    );

    $local_pod_stub_class::staticExpects($this->never())->method('get_data');

    $local_function_stubs = $this->get_user_function_stubs();
    $local_function_stubs->expects($this->never())
      ->method('singleton_view_render');

    UserFunctionStubs::set_active_instance($local_function_stubs);

    $plumber->plumber_pod_class = $local_pod_stub_class;

    PlumberStatic::set_active_instance($plumber);

    PlumberStatic::create_routes($wp_router_stub);
    PlumberStatic::router_callback_get('no-inheritance');
  }


  public function testHTTPMethodHandlingGET() {
    global $wp_router_stub;

    $local_function_stubs = $this->get_user_function_stubs();
    $local_function_stubs->expects($this->exactly(1))
      ->method('singleton_pre_render')
      ->with(
        $this->callback(function($args) {
          return $args['route_vars']['method'] == 'get';
        }))
        ->will($this->returnValue(false)
      );

    UserFunctionStubs::set_active_instance($local_function_stubs);

    PlumberStatic::create_routes($wp_router_stub);
    PlumberStatic::router_callback_get('multi-method');
  }


  public function testHTTPMethodHandlingPOST() {
    global $wp_router_stub;

    $local_function_stubs = $this->get_user_function_stubs();
    $local_function_stubs->expects($this->exactly(1))
      ->method('singleton_pre_render')
      ->with(
        $this->callback(function($args) {
          return $args['route_vars']['method'] == 'post';
        }))
        ->will($this->returnValue(false)
      );

    UserFunctionStubs::set_active_instance($local_function_stubs);

    PlumberStatic::create_routes($wp_router_stub);
    PlumberStatic::router_callback_post('multi-method');
  }


  public function testMultiFunctionsAndFlexibleStringOrArrayInput() {
    global $wp_router_stub;

    $local_function_stubs = $this->get_user_function_stubs();
    $local_function_stubs->expects($this->at(0))
      ->method('singleton_pre_render')
      ->with($this->anything())
      ->will($this->returnValue(array('test')));

    $local_function_stubs->expects($this->at(1))
      ->method('singleton_another_render')
      ->with($this->contains('test'))
      ->will($this->returnValue(array('test_again')));

    $local_function_stubs->expects($this->at(2))
      ->method('singleton_final_render')
      ->with($this->contains('test_again'))
      ->will($this->returnValue(false));

    $local_function_stubs->expects($this->at(3))
      ->method('singleton_post_render')
      ->with($this->contains('test_again'))
      ->will($this->returnValue(false));

    $local_function_stubs->expects($this->at(4))
      ->method('singleton_another_render')
      ->with($this->contains('test_again'))
      ->will($this->returnValue(array('a thing to ignore')));

    $local_function_stubs->expects($this->at(5))
      ->method('singleton_final_render')
      ->with($this->contains('test_again'))
      ->will($this->returnValue(false));

    UserFunctionStubs::set_active_instance($local_function_stubs);

    PlumberStatic::create_routes($wp_router_stub);
    PlumberStatic::router_callback_get('multiple-functions');
  }

}


?>
