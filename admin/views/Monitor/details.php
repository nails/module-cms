<?php

/**
 * @var \Nails\Cms\Factory\Monitor\Detail[] $aSummary
 * @var bool                                $bIsDeprecated
 * @var string                              $sAlternative
 */

if ($bIsDeprecated) {
    ?>
    <div class="alert alert-danger">
        This item is deprecated.
        <?php
        if ($sAlternative) {
            ?>
            Consider using <?=$sAlternative?> instead.
            <?php
        }
        ?>
    </div>
    <?php
}

if (empty($aSummary)) {
    ?>
    <div class="alert alert-warning">
        This item is not used anywhere.
    </div>
    <?php

} else {

    foreach ($aSummary as $oSummary) {
        ?>
        <fieldset>
            <legend><?=$oSummary->label?></legend>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th class="actions" style="width: 175px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($oSummary->usages as $oUsage) {
                            ?>
                            <tr>
                                <td>
                                    <?=$oUsage->label?>
                                </td>
                                <?php
                                echo '<td class="actions">';

                                if ($oUsage->urlView) {
                                    ?>
                                    <a href="<?=$oUsage->urlView?>" class="btn btn-xs btn-default" target="_blank">
                                        View
                                    </a>
                                    <?php
                                }

                                if ($oUsage->urlEdit) {
                                    ?>
                                    <a href="<?=$oUsage->urlEdit?>" class="btn btn-xs btn-primary" target="_blank">
                                        Edit
                                    </a>
                                    <?php
                                }

                                echo '</td>';

                                ?>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </fieldset>
        <?php
    }
}
