# WP Plumber

**THIS PLUGIN IS NOT YET READY FOR PUBLIC USE**

WP Plumber is an MVC plugin for Wordpress that attempts to utilize the best parts of Wordpress: admin UI elements, easy database setup, plugin ecosystem, broad support, media management and image conversion, while freeing the developer from the Wordpress Loop and assumed routing.

WP Plumber leverages WP Router to provide a concise, flexible, routing interface; integrates tightly with Pods to provide highly customizable and easily accessible data models; supports basic php view templates out of the box, but can easily by integrated with external templating libraries.


- [Installation](#installation) 
- [Configuration](#configuration) 
- [Introduction](#introduction) 
- [Routes](#routes) 
- [Route Templates](#route_templates) 
- [Pods](#pods_section) 
- [View Rendering](#view_rendering) 
- [Function Reference](#function_reference) 
- [Examples](#examples) 


---


## Installation <a name="installation"></a>

**MORE DETAILS**

Install and enable **WP Router** and **Pods**.

Enable permalinks, set to custom: /%category%/%post%/

Install and enable **WP Plumber**.


---


## Configuration <a name="configuration"></a>

**MORE DETAILS**

define routes in functions.php

create view templates in views directory

define pods in Pods admin GUI


---


## Introduction <a name="introduction"></a>

WP Plumber's primary goal is to provide a concise interface for defining routes and matching pod data with view templates.

A sequence diagram showing a visitor request to WP Plumber is shown below. All WP Plumber functionality is optional, as some routes may not require pods, many others will not define pre_render or post_render functions, and occasionally (as with redirects) a view may not be rendered at all. That said, the pre_render and post_render functions are dashed in the diagram to indicate that they are less likely to be called for the average request.


![Plumber Sequence Diagram](http://bitsynthesis.com/wp-content/uploads/wp-plumber-sequence-diagram.png)


Configuring WP Router can be as simple as defining some routes in functions.php and creating associated html/php templates in a "views" subdirectory of your theme.


*functions.php*
```php
Plumber::set_routes(array(

  // defines a static homepage
  '^' => array(
    'view_template' => 'pages/home'
  ),

  // defines a basic page with CMS managed settings
  'about-me' => array(
    'pods' => array('settings:about_page_settings'),
    'view_template' => 'pages/basic'
  ),

  // defines a paginated listing page for articles
  'articles/{page_num}' => array(
    'pods' => array('articles'),
    'pod_filters' => array(
      'articles' => array(
        'limit' => 5,
        'page' => '{page_num}'
      )
    ),
    'view_template' => 'pages/articles'
  ),

  // defines an individual article page
  'article/{id}' => array(
    'pods' => array('articles{id}'),
    'view_template' => 'pages/articles/single'
  )

));
```


*views/pages/home.php*
```php
<header>
  <h1>This is the homepage.</h1>
</header>
```


*views/pages/basic.php*
```php
<header>
  <h1><?php echo('$settings['big_title']) ?></h1>
</header>
```


*views/pages/articles.php*
```php
<?php foreach($articles as $article) { ?>

  <article>
    <h2><?php echo($article['published_title']) ?></h2>
    <p><?php echo($article['summary']) ?></p>
  </article>

<?php } ?>
```


*views/pages/articles/single.php*
```php
<article>
  <h1><?php echo($article['published_title']) ?></h1>
  <figure>
    <img src="<?php echo($article['photograph']['guid']) ?>" />
  </figure
  <p><?php echo($article['full_article_text']) ?></p>
</article>
```


---


## Routes <a name="routes"></a>

- [the route path](#the_route_path)
- [view_template](#route_view_template)
- [pods](#route_pods)
- [pod_filters](#route_pod_filters)
- [route_vars](#route_route_vars)
- [pre_render_fn](#route_pre_render_fn)
- [post_render_fn](#route_post_render_fn)
- [route_template](#route_route_template)

Routes are defined as relative URL strings, and may include dynamic variable segments. Each route has an associated array of attributes which includes all the details necessary for executing a request made to that route. This may include: 

- which pods to retrive
- pre_render function for modifying or adding to data for the view
- which view template to use
- post_render function for executing code after everything else

All attributes are optional, and most route definitions will not require all attributes.

A route definition utilizing the full range of attributes is shown below. See the [section on Routes](#routes) for individual attribute documentation.

```php
Plumber::set_routes(array(

  // http://example.com/user/my-user-id
  'user/{id}' => array(

    'view_template' => 'pages/example',

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

    'route_template' => 'global'

  )

));
```


#### the route path <a name="the_route_path"></a>
*string*

Routes are specified by their url respective to the site's home directory, and may include dynamic variables specified by {}.

Routes are checked against requests in the order they are defined, so if multiple routes match a request then only the first will be called.

```php
Plumber::set_routes(array(

  // http://example.com/something
  'something' => array(),

  // http://example.com/something/else
  'something/else' => array(),

  // http://example.com/thing/my-thing-name
  'thing/{thing_name}' => array()

));
```

There are a few special route identifiers as well.

```php
Plumber::set_routes(array(

  // site root
  // http://example.com
  '^' => array(),

  // catch all route for 404 errors; place after all other routes
  '$' => array()

));
```


#### view_template <a name="route_view_template"></a>
*string*

Specify a view template relative to the views directory. This template file is passed to the render function along with the pod data and other args. As such, it can be basic php or a templating format like Liquid or Mustache.


#### pods <a name="route_pods"></a>
*array*

Specify pods to fetch for the route.

Pods are are identified by strings that may have up to three parts.

```php
selector:pod_type{route_var}
```


- **pod_type** (required)
- this is the name of the pod


- **selector** (optional)
- specifies the name of the variable in which the corresponding pod data will be stored; if not included, pod data will be stored in a variable with the same name as the pod.
- allows for routes with different pod types to share templates by providing data in the same variable ('content' in the example below)

```php
Plumber::set_routes(array(

  'about' => array(
    'pods' => array('content:about_page_settings'),
    'view_template' => 'pages/basic'
  ),

  'contact' => array(
    'pods' => array('content:contact_page_settings'),
    'view_template' => 'pages/basic'
  )

));
```


- **route_var** (optional)
- corresponds with a named variable in the route path or route_vars

```php
Plumber::set_routes(array(

  'thing/{thing_num}' => array(
    'pods' => array('things{thing_num}')
  )

));
```


#### pod_filters <a name="route_pod_filters"></a>
*array*

Define filters associated with pods by their selector. This allows for filtering, sorting, limiting, and paginating pods. As with the pods attribute, filter attribute values may contain route_vars denoted by {}. 

Pod filters support all pod "find" options, see "additional parameter options" in the [official documentation](http://pods.io/docs/code/pods/find/)

```php
Plumber::set_routes(array(

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

));
```


#### route_vars <a name="route_route_vars"></a>
*array*

Associative array of attributes and values that will be merged with any dynamic variables defined in the route. In the case of identical keys, variables defined in the route will take precedent over those defined in route_vars. In this way, route_vars provide a way of settings default values in a shared route template for attributes that may be defined dynamically in some routes but not in others.

The first two routes will produce the same results, but http://example.com/articles/2 would produce the second page of articles.

*functions.php*
```php
Plumber::set_routes(array(

  // http://example.com/articles
  'articles' => array(
    route_vars = array('page_num' => 1)
    route_template => 'articles_listing'
  ),

  // http://example.com/articles/1
  'articles/{page_num}' => array(
    route_template => 'articles_listing'
  )

));


Plumber::set_route_templates(array(

  'articles_listing' => array(
    'pods' => array('articles'),
    'pod_filters' => array(
      'articles' => array(
        'page' => '{page_num}'
      )
    ),
    'view_template' => 'pages/articles'
  );

));
```

*views/pages/articles*
```php
<span>This page is number <?php echo($route_vars('page_num')) ?>.</span>

<?php foreach($articles as $article) { ?>
  // render some articles
<?php } ?>
```


#### pre_render_fn <a name="route_pre_render_fn"></a>
*string*

Name of function to call before the view rendering function. The specified function will be called with a single argument: the full array of pod data and route vars. The function may be used to filter, modify, or add to this data, simply returning the altered array. If the function is not intended to modify the data set, but rather to serve some other function, return false to have Plumber retain the original data set.

```php
Plumber::set_routes(array(

  'example' => array(
    'pre_render_fn' => 'capitalize_all_titles'
  )

));


function capitalize_all_titles($args) {
  // capitalize all the titles...
  return $modified_args;
}
```

#### post_render_fn <a name="route_post_render_fn"></a>
*string*

Name of function to call after the view rendering function. The same data set that was passed to the view render function will be supplied as an argument to the post_render_fn. The return value of this function will be ignored.

```php
'post_render_fn' => 'do_something_after_render'
```


#### route_template <a name="route_route_template"></a>
*string*

Specify a template to inherit from.

May contain any or all of the attributes available to route definitions, including route_template for cascading inheritance.



---


## Route Templates <a name="route_templates"></a>

To help DRY your route definitions, Plumber also provides the ability to create Route Templates. These are named route templates from which standard Routes can inherit. Route Templates may also define a template to inherit from, enabling cascading Route Template inheritance.

Route templates may contain any or all of the attributes available to route definitions, including 'route_template' for cascading template inheritance. Instead of a route, route templates are given an arbitrary identifier, 'basic_page' in the example below.

Attributes in assigned templates are only applied if not already defined in the associated route. The exception is pods and pod_filters, which are always merged. Pods and pod_filters with identical selectors will respect the order of inheritance like other attributes.

*functions.php*
```php
Plumber::set_route_templates(array(
  
  'basic_page' => array(
    'pods' => array('settings:global_settings'),
    'view_template' => 'pages/basic'
  )

));


Plumber::set_routes(array(

  // http://example.com/basic/child
  'basic/child' => array(
    'route_template' => 'basic_page'
  )

));
```


---


## Pods <a name="pods_section"></a>

#### Protected Pod Attribute Names
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


---


## View Rendering <a name="view_rendering"></a>

The default views directory is a 'views' subdirectory of your main theme folder. If views are stored elsewhere, assign the directory relative to the main theme directory.

*functions.php*
```php
// path/to/wp/theme/templates
Plumber::set_views_directory('templates');

// path/to/wp/theme
Plumber::set_views_directory('');
```

#### view_render_fn

By default WP Plumber supports rendering of basic PHP templates, simply including them and passing in the pod data, route variables, and user arguments.

Users can also define their own view render functions, thus enabling the use of external templating libraries like Liquid and Mustache. A basic view render function looks like this.

```php
function render_my_view($template, $data) {
  // convert first level of $data keys to local variables, enabling easier
  // access in the views. this is optional, as you could just reference
  // $data as is from within your views
  extract($data);

  // include the template file, full file path provided
  include($template.'.php');
}
```

$data will always have the attribute 'route_vars', which may be empty if no route_vars are defined in the route path or the route_vars attribute. In addition, it contains all the pod data retrieved for the current route. Each array of pod data is stored with a key matching its selector (or its pod type if a selector is not explicitly defined).

The following is an example $data structure:

*functions.php*
```php
Plumber::set_routes(array(

  'articles/{id}' => array(
    'pods' => array(
      'settings:global_settings', 
      'advertisements',
      'articles{id}'
    )
    'route_vars' => array(
      'other' => 'thing'
    )
  )

));
```

*resulting $data structure for http://example.com/articles/2*
```php
$data = array(

  'route_vars' => array(
    'id' => 2,
    'other' => 'thing'
  ),

  'settings' => // global settings pod data

  'advertisements' => // nested array of all individual advertisement pod data

  'articles' => // article pod data for article with id == 2

)
```


#### liquid view_render_fn

To render Liquid templates using the php-liquid library (which must be included separately) along with a class of Liquid helper functions:

```php
function render_liquid_template($template, $data = array()) {
  $liquid = new LiquidTemplate(Plumber::get_views_directory());
  $liquid->registerFilter(new MyLiquidHelpers());

  $liquid->parse(file_get_contents($template.'.liquid'));
  print $liquid->render($data);
}
```


---


## Function Reference <a name="function_reference"></a>

#### set_routes($routes)

#### set_route_templates($route_templates)

#### set_view_render_fn($function_name)

#### set_views_directory($views_directory)

#### get_views_directory()


---


## Examples <a name="examples"></a>

*functions.php*
```php
Plumber::set_routes(array(

  
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
```



