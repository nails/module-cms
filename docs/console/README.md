# Console Commands
> Documentation is a WIP.


The following console tools are provided by this module and can be executed using the [Nails Console Tool](https://github.com/nailsapp/module-console).


| Command             | Description                |
|---------------------|----------------------------|
| `make:cms:template` | Creates a new CMS Template |
| `make:cms:widget`   | Creates a new CMS Widget   |


## Command Documentation



### `make:cms:template [<templateName>] [<templateDescription>]`

Interactively creates a new CMS Template.

#### Arguments & Options

| Argument          | Description                            | Required | Default |
|-------------------|----------------------------------------|----------|---------|
| widgetName        | The name of the widget to create       | no       | null    |
| widgetDescription | The description of the widget          | no       | null    |



### `make:cms:widget [<widgetName>] [<widgetDescription>] [<widgetGrouping>] [<widgetKeywords>]`

Interactively creates a new CMS Widget.

#### Arguments & Options

| Argument          | Description                            | Required | Default |
|-------------------|----------------------------------------|----------|---------|
| widgetName        | The name of the widget to create       | no       | null    |
| widgetDescription | The description of the widget          | no       | null    |
| widgetGrouping    | The sidebar grouping of the widget     | no       | null    |
| widgetKeywords    | The searchable keywords of the widget) | no       | null    |
