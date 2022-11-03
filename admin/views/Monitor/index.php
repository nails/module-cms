<?php

/**
 * @var \Nails\Cms\Factory\Monitor\Item[] $aSummary
 */

/** @var \Nails\Common\Service\Input $oInput */
$oInput = \Nails\Factory::service('Input');

echo Nails\Admin\Helper::loadSearch((object) [
    'keywords'    => $oInput->get('keywords'),
    'searchable'  => true,
    'sortColumns' => [
        'label'  => 'Label',
        'usages' => 'Usages',
    ],
    'sortOn'      => $oInput->get('sortOn') ?: null,
    'sortOrder'   => $oInput->get('sortOrder') ?: null,
    'perPage'     => 0,
]);

?>
<table class="table table-striped table-hover table-bordered table-responsive">
    <thead class="table-dark">
        <tr>
            <th>Image</th>
            <th>Item</th>
            <th>Slug</th>
            <th class="text-center">Usages</th>
            <th class="text-center">In Use</th>
            <th class="actions" style="width: 175px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($aSummary as $oItem) {
            ?>
            <tr>
                <td class="text-center" style="width:300px;">
                    <?php

                    if (!empty($oItem->image)) {
                        echo img(['src' => $oItem->image, 'width' => '100%']);
                    } else {
                        echo '&mdash;';
                    }

                    ?>
                </td>
                <td>
                    <?=$oItem->label?>
                    <small><?=$oItem->description?></small>
                </td>
                <td>
                    <code><?=$oItem->slug?></code>
                </td>
                <td class="text-center">
                    <?=$oItem->usages?>
                </td>
                <?=\Nails\Admin\Helper::loadBoolCell(!$oItem->is_deprecated)?>
                <td class="actions">
                    <a href="<?=current_url() . '/' . $oItem->slug?>" class="btn btn-xs btn-primary">
                        Details
                    </a>
                </td>
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
