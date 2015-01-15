<?php

/**
 * This class is the basic CMS widget definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

class NAILS_CMS_Widget
{
    protected $details;
    protected $data;

    // --------------------------------------------------------------------------

    /**
     * Defines the basic widget details object. Widgets should extend this
     * object and customise to it's own needs.
     * @return stdClass
     */
    public static function details()
    {
        $d      = new stdClass();
        $d->iam = get_called_class();

        $reflect = new ReflectionClass($d->iam);

        $d->label       = 'Widget';
        $d->description = '';
        $d->keywords    = '';
        $d->grouping    = '';

        //  Work out the slug, this should uniquely identify the widget
        $d->slug = $reflect->getFileName();
        $d->slug = pathinfo($d->slug);
        $d->slug = explode('/', $d->slug['dirname']);
        $d->slug = array_pop($d->slug );

        /**
         * If a widget should be restricted to a specific templates or areas
         * then specify the appropriate template slugs below
         */

        $d->restrict_to_template = array();
        $d->restrict_to_area     = array();

        /**
         * If a widget should appear anywhere BUT a certain template or area,
         * then define that here
         */

        $d->restrict_from_template = array();
        $d->restrict_from_area     = array();

        //  Define any assets need to be loaded by the widget
        $d->assets_editor = array();
        $d->assets_render = array();

        //  Path
        $d->path = dirname($reflect->getFileName()) . '/';

        //  Define any JS callbacks; these will be properly scoped by Nails
        $d->callbacks               = new stdClass();
        $d->callbacks->dropped      = '';
        $d->callbacks->sort_start   = '';
        $d->callbacks->sort_stop    = '';
        $d->callbacks->remove_start = '';
        $d->callbacks->remove_stop  = '';

        //  Attempt to auto-populate these fields
        foreach ($d->callbacks as $property => &$callback) {

            if (is_file($d->path . 'js/' . $property . '.min.js')) {

                $callback = file_get_contents($d->path . 'js/' . $property . '.min.js');

            } elseif (is_file($d->path . 'js/' . $property . '.js')) {

                $callback = file_get_contents($d->path . 'js/' . $property . '.js');
            }
        }

        return $d;
    }

    // --------------------------------------------------------------------------

    /**
     * Constructs the template and sets the templates details as a class variable
     */
    public function __construct()
    {
        $this->details = $this::details();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the HTML for the editor view. Any passed data will be used to
     * populate the values of the form elements.
     * @param  array  $wgtData A normal widget_data object, prefixed to avoid naming collisions
     * @return string
     */
    public function get_editor($wgtData = array())
    {
        if (is_file($this->details->path . 'views/editor.php')) {

            //  Extract the variables, so that the view can use them
            if ($wgtData) {

                extract($wgtData);
            }

            //  Start the buffer, basically copying how CI does it's view loading
            ob_start();

            include $this->details->path . 'views/editor.php';

            //  Flush buffer
            $buffer = ob_get_contents();
            @ob_end_clean();

            //  Return the HTML
            return $buffer;
        }

        return '';
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the widget with the provided data.
     * @param  array  $wgtData           The widgets to include in the template
     * @param  array  $tplAdditionalData Additional data created by the template
     * @return string
     */
    public function render($wgtData = array(), $tplAdditionalData = array())
    {
        /**
         * If the template wishes to execute any custom pre/post code then this method
         * should be extended and parent::render($data) called at the appropriate point.
         * But that's obvious, isn't it...?
         */

        if (is_file($this->details->path . 'views/render.php')) {

            //  If passed, extract any controller data
            $NAILS_CONTROLLER_DATA =& getControllerData();

            if ($NAILS_CONTROLLER_DATA) {

                extract($NAILS_CONTROLLER_DATA);
            }

            //  Extract the variables, so that the view can use them
            if ($wgtData) {

                extract($wgtData);
            }

            if ($tplAdditionalData) {

                extract($tplAdditionalData);
            }

            //  Start the buffer, basically copying how CI does it's view loading
            ob_start();

            include $this->details->path . 'views/render.php';

            //  Flush buffer
            $buffer = ob_get_contents();
            @ob_end_clean();

            //  Return the HTML
            return $buffer;
        }

        return '';
    }
}
