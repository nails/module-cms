# Templates
> Documentation is a WIP.


Templates are what CMS Pages use to lay the page out; they define "widget areas" which can be populated by the user with widgets, as well as offering a variety of additional configuration options which can be used by the page to alter the layout, or indeed any aspect of the rendered page.

The following sections assume you are building templates from with the app (and not a Nails compatible module, if this is what you're doing, see the bottom of this page).


## Anatomy of a template

Templates are simply a single class which extends the base template and provides a view file. You can optionally include a screenshot too, for extra bells and whistles. CMS Templates must exist at `application/modules/cms/templates`, and the typical template layout looks like this:

```
/application/
|- cms/
|--- templates/
|------ MyTemplate/
|--------- template.php
|--------- view.php
|--------- icon.png
|--------- icon@2x.png             
```

`template.php` is your template definition, and `view.php` is the HTML which will be rendered. Optionally you can include `icon.png` and `icon@2x.png` which will be rendered in admin and can be a nice UI touch.



## Default templates

There are a number of commonly used templates which are [bundled with the module](/cms/templates). If you're stuck, this can be a good place to look.



## Creating your own templates

> Easily generate CMS Templates using the [Console Command](/docs/console/README.md)

In order for a template to be recognised it must be defined in `template.php`. A basic set up will look like this, notice that the class name matches the directory name:

```php
<?php

namespace App\Cms\Template;

use Nails\Factory;
use Nails\Cms\Template\TemplateBase;

class Fullwidth extends TemplateBase
{
    public function __construct()
    {
        parent::__construct();
        
        $this->label       = 'My Template';
        $this->description = 'A short description about the template';
    }
}
```

The contents of `view.php` is entirely up to you, but bare in mind that no additional views will be loaded (e.g. header and footers) - it is up to you to include anythign you might need using the `View` service.



### Widget areas

Templates can define "widget areas"; this should be self-explanatory, but these are areas which in which the user can palce widgets. CMS Pages admin will detect these areas an offer the user an interface for doing this.


#### Defining the widget area

Every template has a protected property called `widget_areas`. This is an array of `TemplateArea` instances. The key given to each element in `widget_areas` becomes the variable where the rendered widgets will be available to the view.

Widget areas should be defined in the constructor, like so:

```php
<?php

namespace App\Cms\Template;

use Nails\Factory;
use Nails\Cms\Template\TemplateBase;

class Fullwidth extends TemplateBase
{
    public function __construct()
    {
        parent::__construct();
        
        $this->label       = 'My Template';
        $this->description = 'A short description about the template';
        
        $this->widget_areas = [

            //  The main body of the page
            'mainbody' => Factory::factory('TemplateArea', 'nailsapp/module-cms')
                ->setTitle('Main Body'),

            //  The page's sidebar
            'sidebar' => Factory::factory('TemplateArea', 'nailsapp/module-cms')
                ->setTitle('Sidebar'),
        ];
    }
}
```


#### Rendering the widget area
    
Rendered widget areas (i.e translated into a string of HTML) will be available to the view via the widget area's key, in the above example the two areas would be available at `$mainbody` and `$sidebar` respectively.



## Template Options

Aside from widget areas it's also possible to define some global template options. These can be used to configure the template on the fly, e.g. show or hide sidebars, define the number of columns, etc.

Options are easily added via adding items to the `additional_fields` property of the template class. this is an array of `TemplateOption` instances. The value of each field is made available to the view by a variable with the same name as the item's key in the array.

```php
<?php

namespace App\Cms\Template;

use Nails\Factory;
use Nails\Cms\Template\TemplateBase;

class Fullwidth extends TemplateBase
{
    public function __construct()
    {
        parent::__construct();
        
        $this->label       = 'My Template';
        $this->description = 'A short description about the template';
        
        $this->additional_fields = [

            //  The main body of the page
            'mainbody' => Factory::factory('TemplateOption', 'nailsapp/module-cms')
                ->setType('dropdown'),
                ->setKey('number_of_columns'),
                ->setLabel('No. of columns'),
                ->setDefault(2),
                ->setOptions([
                    '2' => '2 Columns',
                    '3' => '3 Columns',
                    '4' => '4 Columns',
                ]),
        ];
    }
}
```



## Overriding templates provided by modules

It's possible for the app to override any template provided by a module. When the Template loader discovers templates it will automatically look for a template of the same name under the `App\Cms\Template` namespace and load that instead of the discovered template. It is then up to the template author to use inheritance as they please to customise the template.

The following example shows the default `Fullwidth` template being overridden to change the name and description; note that the widget areas themselves remain the same (due to inheritance):

```php
<?php

namespace App\Cms\Template;

use Nails\Factory;

class Fullwidth extends \Nails\Cms\Template\Fullwidth
{
    public function __construct()
    {
        parent::__construct();
        
        $this->label       = 'My Full Width Template';
        $this->description = 'I have overridden the default Full Width template.';
    }
}
```

## Bundling templates with your module

The only difference to the above when building a template which is to be supplied by a module is the template's namespace and the fact that it cannot override another module supplied template.

The namespace to use is will incorporate a camel-cased version of your module's name, e.g. for a module in the vendor folder my-vendor/my-module, the namespace would be `Nails\MyModule\Template`.

> @todo - verify this is correct
