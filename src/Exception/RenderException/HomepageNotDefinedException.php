<?php

/**
 * HomepageNotDefined Exception
 *
 * @package    Nails
 * @subpackage module-cms
 * @category   Exceptions
 * @author     Nails Dev Team
 */

namespace Nails\Cms\Exception\RenderException;

use Nails\Cms\Exception\RenderException;

class HomepageNotDefinedException extends RenderException
{
    const DOCUMENTATION_URL = 'https://docs.nailsapp.co.uk/modules/cms/pages/homepage';
}
