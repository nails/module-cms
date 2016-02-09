<?php

/**
 * This class is the "Blockquote" CMS editor view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

?>
<div class="fieldset">
    <?php

    echo form_field_wysiwyg(
        array(
            'key'     => 'quote',
            'label'   => 'Quotation',
            'class'   => 'wysiwyg-basic',
            'default' => !empty($quote) ? $quote : ''
        )
    );

    echo form_field(
        array(
            'key'     => 'cite_text',
            'label'   => 'Citation',
            'default' => !empty($cite_text) ? $cite_text : ''
        )
    );

    echo form_field(
        array(
            'key'     => 'cite_url',
            'label'   => 'Citation URL',
            'default' => !empty($cite_url) ? $cite_url : ''
        )
    );

    ?>
</div>
