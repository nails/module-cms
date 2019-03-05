# Events
> Documentation is a WIP.


This module exposes the following events through the [Nails Events Service](https://github.com/nails/common/blob/master/docs/intro/events.md) in the `nails/module-cms` namespace.

> Remember you can see all events available to the application using `nails events`


- [Pages](#pages)
    - [Nails\Cms\Events::PAGE_CREATED](#page-created)
    - [Nails\Cms\Events::PAGE_UPDATED](#page-updated)
    - [Nails\Cms\Events::PAGE_DELETED](#page-deleted)
    - [Nails\Cms\Events::PAGE_PUBLISHED](#page-published)
    - [Nails\Cms\Events::PAGE_UNPUBLISHED](#page-unpublished)



## Pages

<a name="page-created"></a>
### `Nails\Cms\Events::PAGE_CREATED`

Fired when a CMS Page is created

**Receives:**

> ```
> int $iId The Page ID
> ```


<a name="page-updated"></a>
### `Nails\Cms\Events::PAGE_UPDATED`

Fired when a CMS Page is updated

**Receives:**

> ```
> int       $iId        The Page ID
> \stdClass $oOldObject The old Page object
> ```


<a name="page-deleted"></a>
### `Nails\Cms\Events::PAGE_DELTED`

Fired when a CMS Page is deleted

**Receives:**

> ```
> int $iId The Page ID
> ```


<a name="page-published"></a>
### `Nails\Cms\Events::PAGE_PUBLISHED`

Fired when a CMS Page is published

**Receives:**

> ```
> int $iId The Page ID
> ```


<a name="page-unpublished"></a>
### `Nails\Cms\Events::PAGE_UNPUBLISHED`

Fired when a CMS Page is unpublished

**Receives:**

> ```
> int $iId The Page ID
> ```


