<?php


// create plumber

// $plumber = new Plumber(array(
//   'render' => 'UserFunctionStubs::view_render'));
// 

$plumber = $this->getMockBuilder('Plumber')
  ->setConstructorArgs(array(
    array('render' => 'UserFunctionStubs::view_render')))
  ->setMethods(array(
    'get_absolute_views_directory'))
  ->getMock();


// define routes


$plumber->get(
  '^', array(
    'view_template' => 'pages/home',
    'route_vars' => array(
      'test_var' => 'test_value'
    ),
    'pre_render' => 'UserFunctionStubs::pre_render',
    'post_render' => 'UserFunctionStubs::post_render'));

$plumber->get(
  'contact-us', array(
    'pods' => array('content:contact_page'),
    'view_template' => 'pages/basic_page'));

$plumber->get(
  'articles', array(
    'route_vars' => array(
      'page' => 1
    ),
    'route_template' => 'articles_list_page'));

$plumber->get(
  'articles/{page}', array(
    'route_vars' => array('something' => 'else'),
    'route_template' => 'articles_list_page'));

$plumber->get(
  'article/{id}', array(
    'pods' => array('content:article{id}'),
    'view_template' => 'pages/articles/single'));

$plumber->get(
  'multi-inheritance', array(
    'route_template' => 'simple_template'));

$plumber->get(
  'no-inheritance', array(
    'route_template' => false));

$plumber->get(
  'multi-method', array(
    'route_vars' => array('method' => 'get'),
    'pre_render' => 'UserFunctionStubs::pre_render'));

$plumber->post(
  'multi-method', array(
    'route_vars' => array('method' => 'post'),
    'pre_render' => 'UserFunctionStubs::pre_render'));

$plumber->get(
  'wrong-man', array(
    'pre_render' => 'notreal',
    'post_render' => 'notreal',
    'view_template' => 'notreal',
    'route_template' => 'notreal'));

$plumber->get(
  '*', array(
    'view_template' => 'pages/home'));


// define route templates


$plumber->route_template(
  'list_page', array(
    'pod_filters' => array(
      'list_items' => array(
        'orderby' => 'post_date DESC',    
        'limit' => 3,
        'page' => '{page}'))));

$plumber->route_template(
  'articles_list_page', array(
    'pods' => array('list_items:article'),
    'view_template' => 'pages/articles',
    'route_template' => 'list_page'));

$plumber->route_template(
  'simple_template', array(
    'view_template' => 'pages/simple'));

$plumber->route_template(
  '_default', array(
  'pods' => array('settings:demo_site_settings'),
  'view_template' => 'pages/default'));


?>
