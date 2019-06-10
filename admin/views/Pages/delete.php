<div class="group-cms pages unpublish">
    <?=form_open()?>
    <?=form_hidden('return_to', $sReturnTo)?>
    <p>
        You are about to delete the page "<?=$oPageData->title?>". Please confirm the following options:
    </p>
    <fieldset>
        <legend>Children</legend>
        <?php
        if (!empty($aChildren)) {
            ?>
            <p class="alert alert-danger">
                This page has children which will be deleted if you continue.
            </p>
            <?php
        } else {
            ?>
            <p class="alert alert-success">
                No child pages
            </p>
            <?php
        }
        ?>
    </fieldset>
    <?php
    if ($bRedirects) {
        ?>
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
    }
    ?>
    <div class="admin-floating-controls">
        <button type="submit" class="btn btn-danger">
            Delete Page
        </button>
        <a href="<?=$sReturnTo?>" class="btn btn-default pull-right">
            Cancel
        </a>
    </div>
    <?=form_close()?>
</div>
