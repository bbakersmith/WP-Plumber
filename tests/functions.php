<?php

Plumber::set_routes(array(

  // 0
  '^' => array(
    'view_template' => 'pages/home'
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
  
));


Plumber::set_route_templates(array(

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

));


Plumber::set_view_render_fn('render_demo_view');


function render_demo_view($template, $args) {
  
  // convert args to local variables for easier access in views
  extract($args);

  $views_directory = Plumber::get_views_directory();

  include($views_directory.'/header.php');
  include($template.'.php');
  include($views_directory.'/footer.php');

}

?>
