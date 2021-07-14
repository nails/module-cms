<?php

/**
 * @var \Nails\Cms\Factory\Monitor\Item[] $aSummary
 */

/** @var \Nails\Common\Service\Input $oInput */
$oInput = \Nails\Factory::service('Input');

echo adminHelper('loadSearch', (object) [
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
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Slug</th>
                <th class="text-center">Usages</th>
                <th class="actions" style="width: 175px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($aSummary as $oItem) {
                ?>
                <tr>
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
</div>
