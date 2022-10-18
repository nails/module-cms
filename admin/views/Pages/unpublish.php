<div class="group-cms pages unpublish">
    <?=form_open()?>
    <?=form_hidden('return_to', $sReturnTo)?>
    <p class="mb-3">
        You are about to unpublish the page "<?=$oPage->published->title?>". Please confirm the following options:
    </p>
    <fieldset>
        <legend>Children</legend>
        <?php
        if (!empty($aChildren)) {
            ?>
            <p class="alert alert-warning m-1">
                This page has children, please decide how they should be processed
            </p>
            <?php
            echo form_field_dropdown([
                'key'     => 'child_behaviour',
                'label'   => 'Behaviour',
                'class'   => 'select2',
                'options' => [
                    'NONE'      => 'Do not make any changes to child pages',
                    'UNPUBLISH' => 'Unpublish all child pages',
                ],
            ]);
        } else {
            ?>
            <p class="alert alert-success m-0">
                No child pages
            </p>
            <?php
        }
        ?>
    </fieldset>
    <fieldset>
        <legend>Redirects</legend>
        <?php
        echo form_field_dropdown([
            'key'     => 'redirect_behaviour',
            'label'   => 'Behaviour',
            'class'   => 'select2',
            'options' => [
                'NONE'               => 'Do not create any redirects',
                'Redirect to a URL'  => [
                    'URL' => 'Redirect to a specific URL',
                ],
                'Redirect to a page' => $aOtherPages,
            ],
        ]);

        echo form_field([
            'key'   => 'redirect_url',
            'label' => 'URL',
        ])

        ?>
    </fieldset>
    <?php

    echo \Nails\Admin\Helper::floatingControls([
        'save' => [
            'text'  => 'Unpublish Page',
            'class' => 'btn btn-warning',
        ],
        'html' => [
            'center' => anchor($sReturnTo, 'Cancel', 'class="btn btn-default pull-right"'),
        ],
    ]);
    echo form_close();

    ?>
</div>
