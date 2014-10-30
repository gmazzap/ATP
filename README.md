ajax_template_part
==================

This plugin adds **`ajax_template_part()`** function to WordPress.

It works like [`get_template_part()`](http://codex.wordpress.org/Function_Reference/get_template_part),
but **ajax powered**.

#What does "ajax powered" mean?

When files containing `ajax_template_part()` calls are loaded by WordPress, required
templates are **not** loaded immediately, only when entire page is loaded, an ajax request is sent to
server to get all the templates required, and related content is *pushed* in right place.

# First question is:

> Why should I run 2 HTTP requests (the *regular* one plus the ajax one)
> to show my template, when using `get_template_part()` I run only one?


**Two reasons:**

### 1. Quick response

Templates may require *some* time to be rendered. Let's imagine a `loop.php` template
that shows a set of posts, each containing a shortcode that renders *things* dynamically.
Requiring that template using *standard* way will slow down page loading. However, sometimes
may be desirable to give a quick response to user that *lands* to the page and *defer* the loading
of slow things (maybe showing a loading UI).

That is possibly more useful to render "secondary" contents: e.g. load a post content as soon as possible,
while a section with related posts, ads or stuff alike are loaded via ajax in meanwhile.

### 2. Cache

`ajax_template_part()` embed a powerful cache system: *all* the contents loaded via ajax
are cached. That means that using this single, core-alike function is possible to implement
a *deferred* fragment cache system, without having to change existing code, to use additional libraries
or to setup anything.
More on plugin cache system [later in this page](#cache).

----

#Requirements

- PHP 5.4+
- WordPress 3.9+
- [Composer](https://getcomposer.org/) to install

----

#Installation

The plugin is a Composer package and can be installed in plugin directory via:

``` bash
composer create-project gmazzap/ajax-template-part --no-dev
```


----

#How to use

`ajax_template_part()` works just like `get_template_part()`

Accepts 2 arguments:

 - `$slug` The slug name for the generic template (required)
 - `$name` The name of the *specialized* template (optional)

e.g. if the function is called like so:

``` php
ajax_template_part( 'content' );
```

it will look for `content.php`, first in child theme folder (if any), then in parent theme.

If called like so:

``` php
ajax_template_part( 'content', 'page' );
```

it will load via ajax the first that exists among, in order:

- `content-page.php` in child theme
- `content-page.php` in parent theme
- `content.php` in child theme
- `content.php` in parent theme

## Show content while loading

By default, where the function is called inside containing template, nothing appear until the
ajax template is not loaded.
However, is possible to show *something*: a spinner image, a loading message, default text..

That can be done in 2 ways:

- via filter
- using the **`ajax_template_part_content()`** function.

### Via filter

Plugin provides the filter **`"ajax_template_loading_content"`** and whatever is returned by hooking
callbacks is used as temporary content until required template is loaded.
Using this technique is possible to use a different contents for different calls thanks to the fact
that hooking callbacks receive 4 arguments

- the current content, that is an empty string, by default
- the "name" of the template required, that is 1st argument passed to function
- the "slug" of the template required, that is 2nd argument passed to function
- current main query object

### Via `ajax_template_part_content()`

This function works in a pretty similar way to `ajax_template_part()`, but takes 3 arguments:
whatever is passed as 1st argument is shown as temporary content, the other 2 arguments are the same
of `ajax_template_part()`.
Note that content passed to this function is not filtered using `"ajax_template_loading_content"` hook.

## Temporary container class

When a temporary content is set, via filter or using `ajax_template_part_content()`, it is added wrapped
inside a `<div>` tag.

Is possible to set HTML class attribute for this container using
**`"ajax_template_loading_class"`** filter hook.

Callbacks hooking this filter receive same 4 arguments passed by `"ajax_template_loading_content"`.

Note that `<div>` container is always added to page, even when no temporary content is used, but
by default it is hidden using in-line CSS, but using `"ajax_template_loading_class"` filter, is
possible to add classes to the `<div>` and so be able to style it via CSS: among other things is possible
to use a *loader image* by setting it as background image CSS property of a class
added via this filter.


## Nested calls

If in template loaded using `ajax_template_part()` there are additional calls to same function,
*nested* templates are loaded as expected, and in the same ajax request: don't expect
*another* ajax request triggered when *parent* ajax-required template as been loaded.

That applies in the exact manner to any `get_template_part()` call inside ajax loaded templates.

## Stay safe

To put `ajax_template_part()` calls in your templates makes your site *require* this plugin is
installed, and active, otherwise you'll get fatal error because of function not declared.

To avoid such problems, e.g. if you deactivate plugin by accident, can be a good idea put this on top
of your `functions.php`:

``` php
if ( ! function_exists( 'ajax_template_part' ) ) {
  function ajax_template_part( $name = '', $slug = '' ) {
    return get_template_part( $name, $slug );
  }
}
if ( ! function_exists( 'ajax_template_part_content' ) ) {
  function ajax_template_part_content( $content = '', $name = '', $slug = '' ) {
    return get_template_part( $name, $slug );
  }
}
```

In this way your theme will *gracefully degrade* to `get_template_part` if plugin is not active for
any reason.

----

# Cache

Ajax template loading and content generation may be heavy, so plugin *needs* a way to cache them.

Cache is active by default when the constant `WP_DEBUG` is set to `FALSE`, this should be a pretty
common way to turn it on for production and off in locale / development environments.

However, using the **`"ajax_template_cache"`** filter is possible to customize when enable or disable caching.
Only argument this hook passes to hooking callbacks is the current cache status and have to return
a boolean: `TRUE` means cache active.

This plugin can work with different types of caches:

- if in the system is installed an external object cache, then this plugin will use that, nothing is left to do.
- if no external object cache is in use, this plugin uses [Stash](http://www.stashphp.com) to cache data.
This library can make use of different "drivers": FileSystem, APC, Memcached, Redis...
By default plugin uses FileSystem driver and *no* configuration is required to use that.
However is possible to use any supported driver, if system has the requirements.

## Use an alternative cache driver

To use a different driver there are 2 filters available:

- **`"ajax_template_cache_driver"`**: hooking callback must return the fully qualified name
of the class to use, one among:
  - `Stash\Driver\Sqlite`
  - `Stash\Driver\Memcache`
  - `Stash\Driver\APC`
  - `Stash\Driver\Redis`
  - `Stash\Driver\Composite`

 please refer to [Stash documentation](http://www.stashphp.com/Drivers.html) for details.

- **`"ajax_template_{$driver}_driver_conf"`** is the filter to be used to configure the chosen driver.
 Hooking callback must return the configuration array, see Stash documentation for details.

 As example, configuration to use Memcache driver should be something like this:

 ```php
 add_filter( 'ajax_template_cache_driver', function() {
   return '\Stash\Driver\Memcache';
 });

 add_filter( "ajax_template_memcache_driver_conf", function() {
   return [ 'servers' => ['127.0.0.1', '11211'] ];
 });
 ```

## Cache expiration

By default contents are cached for 1 hour. Consider that if content (e.g. posts) is updated
and the old content is cached, updated content will not be shown until cache expires.

Is possible to change default expiration time using `"ajax_template_cache_ttl"` filter hook.
Hooking callbacks receives and have to return cache "time to live" value in seconds.
Note that setting a value under 30 seconds will be skipped and default will be used.

If you need to disable cache don't use this filter but `"ajax_template_cache"` or set `WP_DEBUG` to true
(not recommended for production, highly recommended for development environments).

---------

#License

This plugin is released under MIT license.
