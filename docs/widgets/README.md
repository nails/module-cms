# Widgets

Widgets are the underlying work horse of the CMS and range from being incredibly simple to extremely complex. There is no real limit to what widgets can and cannot do.

The following sections assume you are building templates from with the app (and not a Nails compatible module, if this is what you're doing, see the bottom of this page).



## Anatomy of a widget

@todo - explain the anatomy of a widget

```
/application/
|- cms/
|--- widgets/
|------ MyWidget/
|--------- widget.php
|--------- views/render.php
|--------- views/editor.php
|--------- js/dropped.js
                
```

`widget.php` is your widget definition. `views/editor.php` is used in the admin widget area GUI and allows you to offer various options for the user to choose from. `views/render.php` is the widgets "view" and is what is processed when the widget is rendered (and passed data, defined in `editor.php`).


### The Admin interface in detail

    @todo - explain how to use the admin interface, what's done automatically etc


## Default widgets

There are a number of commonly used widgets which are [bundled with the module](/cms/widgets). If you're stuck, this can be a good place to look.


## Creating your own widgets

> The Nails Command Line tool makes this easy! Use `nails cms:widget` to automatically create the files and classes you need.

    @todo - explain how to create your own widgets



## Overriding widgets provided by modules

    @todo - explain how to override widgets


## Bundling templates with your module

    @todo - explain the differences when bundling templates in a module
