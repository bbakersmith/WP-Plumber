# WP Plumber

WP Plumber is an MVC plugin for Wordpress. 

WP Plumber attempts to utilize the best parts of Wordpress: admin UI elements, easy database setup, plugin ecosystem, broad support, media management and image conversion, while freeing the developer from the Wordpress loop by providing 
leverages WP Router to provide a concise, flexible, routing interface; integrates tightly with Pods to provide highly customizable and easily accessible data models; supports basic php view templates out of the box, and can easily by integrated with user-defined view templating libraries or functions.



## Installation

Install and enable **WP Router** and **Pods**.

Enable permalinks, set to custom: /%category%/%post%/

Install and enable **WP Plumber**.



## Configuration

define routes in functions.php

create view templates in views directory

create pods in wp admin pods interface



## Routing Example

:::php

// functions.php

Plumber::set_routes(arary(

  
  // basic page 
  // http://example.com/basic
  'basic' => array(
    'view_template' => 'pages/basic'
  ),


  // basic page with user-managed content
  // http://example.com/basic/custom
  'basic/custom' => array(
    'pods' => array('custom_page_settings'),
    'view_template' => 'pages/custom'
  ),


  // basic page inheriting route template
  // (see below route definitions)
  // http://example.com/basic/child
  'basic/child' => array(
    'route_template' => 'basic_page'
  ),


  // paginated listing
  // http://example.com/articles/1
  'articles/{page_num}' => array(
    'pods' => array('articles'),
    'pod_filters' => array(

      'articles' => array(
        'orderby' => 'post_date DESC',
        'limit' => 8,
        'page' => '{page_num}'
      )

    ),
    'view_template' => 'pages/articles'
  ),


  // individual item
  // http://example.com/article/my-first-article
  'article/{slug}' => array(
    'pods' => array('articles{slug}'),
    'view_template' => 'pages/single_article'
  )


));

:::


## Routes

A route definition utilizing the full range of attributes is shown below, and following is the documentation for each attribute.

All attributes are optional.

:::php

'example/user/{id}' => array(

  'route_template' => 'global',

  'pods' => array('users{id}', 'settings:user_page_settings', 'advertisements'),

  'pod_filters' => array(
    'advertisements' => array(
      'limit' => 2
    )
  ),

  'route_vars' => array(
    'censorship_level' => 15,
    'something_else' => 'barracuda'
  ),

  'pre_render_fn' => 'censor_content',

  'post_render_fn' => 'log_something',

  'view_template' => 'pages/example'

)

:::


### the route path
*string*

Routes are specified by their url respective to the site's home directory, and may include dynamic variables specified by {}.

Routes are checked against requests in the order they are defined, so if multiple routes match a request then only the first will be called.

:::php

// http://example.com/something
'something' => array();

// http://example.com/something/else
'something/else' => array();

// http://example.com/thing/my-thing-name
'thing/{thing_name}' => array();

:::

There are a few special route identifiers as well.

:::php

// site root
// http://example.com
'^' => array();

// catch all route for 404 errors; place after all other routes
'$' => array();

:::


### route_template
*string*

Specify a template to inherit from.

May contain any or all of the attributes available to route definitions, including route_template for cascading inheritance.


### view_template
*string*

Specify a view template relative to the views directory. This template file is passed to the render function along with the pod data and other args. As such, it can be basic php or a templating format like Liquid or Mustache.


### pods
*array*

Specify pods to fetch for the route.

Pods are are identified by strings that may have up to three parts.

:::
selector:pod_type{query_var}
:::

- **selector**
  - optional; specifies the name of the variable in which the corresponding pod data will be stored; if not included, pod data will be stored in a variable with the same name as the pod.
  - allows for routes with different pod types to share templates by providing data in the same variable

:::
'content:about_page_settings'

'content:contact_page_settings'
:::

- **pod_type**
  - required; this is the name of the pod

- **query_var**
  - optional; corresponds with a named variable in the route path or route_vars

:::php

'thing/{thing_num}' => array(
  'pods' => array('things{thing_num}')
)

:::


### pod_filters
*array*

Define filters associated with pods by their selector. This allows for filtering, sorting, limiting, and paginating pods. As with pods, filter attribute values may contain route_vars denoted by {}. 

:::php

'stuff/{page_num}' => array(
  'pods' => array('things'),
  'pod_filters' => array(

    'things' => array(
      'orderby' => 'post_date DESC',
      'limit' => 8,
      'page' => '{page_num}'
    )

  )
)

:::


### route_vars
*array*

Associative array of attributes and values that will be merged with any dynamic variables defined in the route. In the case of identical keys, variables defined in the route will take precedent over those defined in route_vars. In this way, route_vars provide a way of settings default values in a shared route template for attributes that may be defined dynamically in some routes but not in others.

The first two routes will produce the same results, but http://example.com/articles/2 would produce the second page of articles.

:::php

// http://example.com/articles
'articles' => array(
  route_vars = array('page_num' => 1)
  route_template => 'articles_listing'
)

// http://example.com/articles/1
'articles/{page_num}' => array(
  route_template => 'articles_listing'
)

:::


### pre_render_fn
*string*

Name of function to call before the view rendering function. The specified function will be called with a single argument: the full array of pod data and route vars. The function may be used to filter, modify, or add to this data, simply returning the altered array. If the function is not intended to modify the data set, but rather to serve some other function, return false to have Plumber retain the original data set.

:::php

'pre_render_fn' => 'capitalize_all_titles'

:::

### post_render_fn
*string*

Name of function to call after the view rendering function. The same data set that was passed to the view render function will be supplied as an argument to the post_render_fn. The return value of this function will be ignored.


---


## Route Templates

Route templates may contain any or all of the attributes available to route definitions, including 'route_template' for cascading template inheritance. Instead of a route, route templates are given an arbitrary identifier, 'basic_page' in the example below.

Attributes in assigned templates are only applied if not already defined in the associated route. The exception is pods and pod_filters, which are always merged. Pods and pod_filters with identical selectors will respect the order of inheritance like other attributes.

:::php

// functions.php

Plumber::set_route_templates(array(

  
  'basic_page' => array(
    'pods' => array('settings:global_settings', 'featured_articles'),
    'view_template' => 'pages/basic'
  )


));

:::


---


## View Rendering

The default views directory is a 'views' subdirectory of your main theme folder. If views are stored elsewhere, assign the directory relative to the main theme directory.

:::php

// functions.php

// path/to/wp/theme/templates
Plumber::set_views_directory('templates');

// path/to/wp/theme
Plumber::set_views_directory('');

:::

By default WP Plumber supports rendering of basic PHP templates, simply including them and passing in the pod data, query variables, and user arguments. 


---


## Pods

### Protected Pod Attribute Names
There are a number of attributes which are available by default from Pods. These field names include the following, and should be avoided when creating new custom pods. The attributes below are included in the pod data supplied to all render functions, and are therefore accessible to templates alongside the custom pod fields.

  - ID
  - post_author
  - post_date
  - post_date_gmt
  - post_content
  - post_title
  - post_excerpt
  - post_status
  - comment_status
  - ping_status
  - post_password
  - post_name
  - to_ping
  - pinged
  - post_modified
  - post_modified_gmt
  - post_content_filtered
  - post_parent
  - guid
  - menu_order
  - post_type
  - post_mime_type
  - comment_count
  - filter
  - ancestors
  - post_category
  - tags_input
  - permalink
  - title


