# Widgets

Widgets are the underlying work horse of the CMS and range from being incredibly simple to extremely complex. There is no real limit to what widgets can and cannot do.

The following sections assume you are building widgets from within the app (and not a Nails compatible module, if this is what you're doing, see the bottom of this page).



## Anatomy of a widget

Widgets are essentially a single class with two companion views: one for the front end and one for the back end. Users can place widgets into widget areas and define properties based on the `<form>` contents of `views/editor.php`.

In addition, widgets can supply some custom JS to enhance the admin experience (e.g. dynamic fields). A basic widget directory tree looks like this:

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



## Widget helpers

The helper `cmsWidget($sSlug, $aData)` is available for rendering widgets on their own; the 1st parameter is the widget's slug/classname and the second is any key:value data you wish to pass to the render view.

```php
<h1>An Example</h1>
<?=cmsWidget('MyWidget', ['body' => '<p>This is some body text.</p>'])?>
```



## Creating your own widgets

> The Nails Command Line tool makes this easy! Use `nails cms:widget` to automatically create the files and classes you need.

In order for a widet to be recognised it must be defined in `widget.php`. A basic set up looks like this, notice that the class name matches the directory name:

```php
<?php

namespace Nails\Cms\Widget;

use Nails\Cms\Widget\WidgetBase;

class MyWidget extends WidgetBase
{
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'My Widget';
        $this->grouping    = 'Generic';
        $this->description = 'A short description about the widget';
        $this->keywords    = 'some,searchable,keywords';
    }
}
```

### Widget's editor view

The editor view is optional, but if provided is a means for you to offer the user some configurable options (e.g. text input or select an option). The structure of this file is up to you - the wiget editor view will automatically parse out form input elements and save them as variables. Once saved they are made available to the view, so they can be repopulated.

```php
<?php

$sBodyText = !empty($body) ? $body : '';

echo form_field_textarea(
    array(
        'key'     => 'body',
        'label'   => 'Body Text',
        'default' => $sBodyText
    )
);
```


### Widget's render view

This view is what is rendered in the front end when a widget area is rendered. It is a basic PHP view which is passed the form input elements from the editor as variables which match the input names.

```php
<?php

$sBodyText = !empty($body) ? $body : '';

if (!empty($sBodyText)) {
    ?>
    <div class="cms-widget">
        <?=$sBodyText?>
    </div>
    <?
}
```


### Widget Javascript

Additional Javascript is optional, but if available will be called each time a new instance of the wodget is added to the widget editor interface. It will be called within a closure which makes the DOM element available via a variable called `domElement`; use this to bind custom actions to items within the editor interface.



## Overriding widgets provided by modules

    @todo - explain how to override widgets


## Bundling templates with your module

    @todo - explain the differences when bundling templates in a module
