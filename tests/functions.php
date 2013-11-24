<?php

Plumber::set_routes(array(

  '^' => array(
    'view_template' => 'pages/home'
  ),

  'contact-us' => array(
    'pods' => array('content:contact_page'),
    'view_template' => 'pages/basic_page'
  ),

  'articles' => array(
    'route_vars' => array(
      'page' => 1
    ),
    'route_template' => 'articles_list_page'
  ),

  'articles/{page}' => array(
    'route_template' => 'articles_list_page'
  ),

  'article/{id}' => array(
    'pods' => array('content:article{id}'),
    'view_template' => 'pages/articles/single'
  ),

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
