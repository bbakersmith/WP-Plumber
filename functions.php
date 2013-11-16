<?php

$routes = array(

  '$' => array(
    'pods' => array('homepage_settings'),
    'view_template' => 'pages/home',
    'route_template' => 'global'
  ),

//   array(
//     'pods' => array('global_settings', 'homepage_settings'),
//     'view_template => 'pages/home'
//   );

  '^about$' => array(
    // get pod by slug or id
    'pods' => 'basic_pages:about', 
    'route_template' => 'basic_page'
  ),

//   array(
//     'pods' => array('global_settings', 'basic_page_settings', 'basic_pages:about'),
//     'pre_processor' => 'parse_basic_page_title',
//     'view_template' => 'pages/basic'
//   );

  '^contact-us$' => array(
    'pods' => 'contact_page',
    'view_template' => 'pages/contact',
    'route_template' => 'basic_page'
  ),

//   array(
//     'pods' => array('global_settings', 'basic_page_settings', 'basic_pages:about'),
//     'pre_processor' => 'parse_basic_page_title',
//     'view_template' => 'pages/contact'
//   );

  '^robbers' => array(
    'pods' => array('global_settings', 'robber_settings'), 
    // get merged into args array along with pod data and passed to pre_processor (or view)
    'additional_args' => array('order' => 'date DESC', 'pod_type' => 'robbers'), 
    // get_pod_list will use additional_args to pull in sorted robbers pods
    'pre_processor' => 'get_pod_list', 
    'view_template' => 'pages/robbers/single'
  ),

  // store url params automatically in argument array, under url_args
  '^robbers/(.*)' => array(
    // get pod by slug or id using url arg
    'pods' => array('global_settings', 'robber_settings', 'robbers:[1]'), 
    'pre_processor' => 'function_name',
    'view_template' => 'pages/robbers/single'
  )

);


$route_templates = array(

  'global' => array(
    'pods' => array('global_settings'),
  )

  'basic_page' => array(
    // create option to set pre_processor class other than router class
    'pods' => 'basic_page_settings',
    'pre_processor' => 'parse_basic_page_title', 
    'view_template' => 'pages/basic',
    'route_template' => 'global' // set a fallback to cascade
  )

);


Plumber.initialize_routes(array(
  'route_templates' => $route_templates,
  'routes' => $routes
));

add_action('wp_router_generate_routes',
           __NAMESPACE__.'\Plumber.get_route_definitions',
           20);

// hopefully this can be called from Plumber
// add_action('wp_router_generate_routes',
//            __NAMESPACE__.'\Plumber.generate_route_definitions',
//            20);
