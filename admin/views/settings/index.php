<div class="group-cms settings">
    <?php

        echo form_open();
        $sActiveTab = $this->input->post('active_tab') ?: 'tab-homepage';
        echo '<input type="hidden" name="active_tab" value="' . $sActiveTab . '" id="active-tab">';

    ?>
    <ul class="tabs" data-active-tab-input="#active-tab">
        <?php

        if (userHasPermission('admin:cms:settings:homepage')) {

            ?>
            <li class="tab">
                <a href="#" data-tab="tab-homepage">Homepage</a>
            </li>
            <?php
        }

        ?>
    </ul>
    <section class="tabs">
        <?php

        if (userHasPermission('admin:cms:settings:homepage')) {

            ?>
            <div class="tab-page tab-homepage">
                <?php

                $aField            = array();
                $aField['key']     = 'homepage';
                $aField['label']   = 'Homepage';
                $aField['class']   = 'select2';
                $aField['default'] = isset($settings[$aField['key']]) ? $settings[$aField['key']] : false;

                echo form_field_dropdown($aField, $publishedPages);

                ?>
            </div>
            <?php
        }

    ?>
    </section>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"')?>
    </p>
    <?=form_close()?>
</div>
