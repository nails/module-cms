# Templates

@todo - explain what templates are and how to use them



## Creating Templates

@todo - explain how to create a new template



## Template Options

@todo - explain the various options available to templates (both cinfigurable ones and method driven ones)



## Overriding Templates

It's possible for the app to override any template provided by a module. When the Teplate laoder discovers templates it will automatically look for a template of the same name under the `App\Cms\Template` namespace and load that instead of the discovered template. It is then up to the template author to use ingeritance as they please to customise the template.

The following example shows the default `Fullwidth` template being overridden to change the name and description; note that the editable areas themselves remain the same (due to inheritance):

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

