<?php

// routes

// 0
Plumber::add_route(
  '^', array(
    'view_template' => 'pages/home',
    'route_vars' => array(
      'test_var' => 'test_value'
    ),
    'pre_render' => 'UserFunctionStubs::pre_render',
    'post_render' => 'UserFunctionStubs::post_render'));

// 1
Plumber::add_route(
  'contact-us', array(
    'pods' => array('content:contact_page'),
    'view_template' => 'pages/basic_page'));

// 2
Plumber::add_route(
  'articles', array(
    'route_vars' => array(
      'page' => 1
    ),
    'route_template' => 'articles_list_page'));

// 3 
Plumber::add_route(
  'articles/{page}', array(
    'route_vars' => array('something' => 'else'),
    'route_template' => 'articles_list_page'));

// 4
Plumber::add_route(
  'article/{id}', array(
    'pods' => array('content:article{id}'),
    'view_template' => 'pages/articles/single'));

// 5
Plumber::add_route(
  'multi-inheritance', array(
    'route_template' => 'simple_template'));

// 6
Plumber::add_route(
  'no-inheritance', array(
    'route_template' => false));

// 7.a
Plumber::add_route(
  'multi-method', array(
    'http_method' => 'GET',
    'route_vars' => array('method' => 'get'),
    'pre_render' => 'UserFunctionStubs::pre_render'));

// 7.b
Plumber::add_route(
  'multi-method', array(
    'http_method' => 'POST',
    'route_vars' => array('method' => 'post'),
    'pre_render' => 'UserFunctionStubs::pre_render'));

// 8
Plumber::add_route(
  'wrong-man', array(
    'pre_render' => 'notreal',
    'post_render' => 'notreal',
    'view_template' => 'notreal',
    'route_template' => 'notreal'));

// 9
Plumber::add_route(
  '*', array(
    'view_template' => 'pages/home'));

// templates

Plumber::add_route_template(
  'list_page', array(
    'pod_filters' => array(
      'list_items' => array(
        'orderby' => 'post_date DESC',    
        'limit' => 3,
        'page' => '{page}'))));

Plumber::add_route_template(
  'articles_list_page', array(
    'pods' => array('list_items:article'),
    'view_template' => 'pages/articles',
    'route_template' => 'list_page'));

Plumber::add_route_template(
  'simple_template', array(
    'view_template' => 'pages/simple'));

Plumber::set_route_defaults(array(
  'pods' => array('settings:demo_site_settings'),
  'view_template' => 'pages/default'));

// callback functions

Plumber::set_view_render('UserFunctionStubs::view_render');

?>
