<?php

use Nails\Admin\Helper;
use Nails\Factory;

/** @var \Nails\Common\Service\Input $oInput */
$oInput = Factory::service('Input');

$oPage  = $cmspage ?? null;
$oDraft = $oPage?->draft;

$mPostedTemplateData = $oInput->post('template_data');
if (is_string($mPostedTemplateData) && $mPostedTemplateData !== '') {
    $mTemplateData = json_decode($mPostedTemplateData);
} else {
    $mTemplateData = $oDraft?->template_data;
}

$sTemplateData = htmlspecialchars(
    (string) json_encode($mTemplateData),
    ENT_QUOTES,
    'UTF-8'
);

$aSlugsWithoutAreas   = [];
$aSlugsWithoutOptions = [];

foreach ($templates as $oTemplateGroup) {
    foreach ($oTemplateGroup->getTemplates() as $oTemplate) {
        if (empty($oTemplate->getWidgetAreas())) {
            $aSlugsWithoutAreas[] = $oTemplate->getSlug();
        }
        if (empty($oTemplate->getAdditionalFields())) {
            $aSlugsWithoutOptions[] = $oTemplate->getSlug();
        }
    }
}

$bShowNoAreas   = in_array($defaultTemplate, $aSlugsWithoutAreas, true);
$bShowNoOptions = in_array($defaultTemplate, $aSlugsWithoutOptions, true);

$sNoAreas   = htmlspecialchars(implode(',', $aSlugsWithoutAreas), ENT_QUOTES, 'UTF-8');
$sNoOptions = htmlspecialchars(implode(',', $aSlugsWithoutOptions), ENT_QUOTES, 'UTF-8');

$sSave = <<<'HTML'
<button type="submit" name="action" value="SAVE" class="btn btn-primary" aria-label="Saves a draft. The live page does not change.">
    Save Changes
</button>
HTML;

$sPublish = <<<'HTML'
<button type="submit" name="action" value="PUBLISH" class="btn btn-success" aria-label="Saves and publishes. The live page updates immediately.">
    Publish Changes
</button>
HTML;

$sPreview = <<<'HTML'
<span class="cms-page-actions-end">
    <button type="button" class="btn btn-default js-cms-page-preview">
        Preview
    </button>
</span>
HTML;

?>
<div class="group-cms pages edit">
    <?php

    if ($oPage && $oPage->is_published && $oPage->published->hash !== $oPage->draft->hash) {
        ?>
        <p class="alert alert-warning">
            <strong>You have unpublished changes.</strong>
            This version of the page is more recent than the version currently published.
            Save a draft, or publish when you want it to go live.
        </p>
        <?php
    }

    echo form_open(null, 'id="cms-page-form" data-unsaved-changes');

    ob_start();

    ?>
        <fieldset>
            <?php

            echo form_field([
                'key'         => 'title',
                'label'       => 'Title',
                'default'     => html_entity_decode((string) ($oDraft->title ?? ''), ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                'placeholder' => 'The title of the page',
                'max_length'  => 255,
            ]);

            echo form_field([
                'key'         => 'slug',
                'label'       => 'Slug',
                'default'     => $oDraft->slug_end ?? '',
                'placeholder' => 'Leave blank to generate a slug from the title',
                'tip'         => 'The last part of the URL. The parent page supplies the rest.',
                'max_length'  => 150,
            ]);

            $aField = [
                'key'              => 'parent_id',
                'label'            => 'Parent Page',
                'placeholder'      => 'The Page\'s parent.',
                'class'            => 'select2',
                'default'          => $oDraft->parent_id ?? null,
                'disabled_options' => $page_children ?? [],
            ];

            if ($oPage) {
                foreach ($pagesNestedFlat as $id => $label) {
                    if ($id == $oPage->id) {
                        $aField['disabled_options'][] = $id;
                        break;
                    }
                }
            }

            if (count($pagesNestedFlat) && count($aField['disabled_options']) < count($pagesNestedFlat)) {
                $pagesNestedFlat = ['' => 'No Parent Page'] + $pagesNestedFlat;
                echo form_field_dropdown($aField, $pagesNestedFlat);
            } else {
                echo form_hidden($aField['key'], '');
            }

            ?>
        </fieldset>
    <?php

    $sDetailsTab = ob_get_clean();
    ob_start();

    ?>
        <fieldset>
            <legend>Template</legend>
            <?=form_error('template', '<div class="alert alert-danger">', '</div>')?>
            <ul class="templates">
                <?php

                $iNumTemplateGroups = count($templates);
                foreach ($templates as $oTemplateGroup) {

                    if ($iNumTemplateGroups > 1) {
                        ?>
                        <li class="template-group-label">
                            <?=htmlspecialchars($oTemplateGroup->getLabel(), ENT_QUOTES, 'UTF-8')?>
                        </li>
                        <?php
                    }

                    foreach ($oTemplateGroup->getTemplates() as $oTemplate) {

                        $sSlug     = $oTemplate->getSlug();
                        $sSlugAttr = htmlspecialchars($sSlug, ENT_QUOTES, 'UTF-8');
                        $bIsSelected = $defaultTemplate == $sSlug;

                        ?>
                        <li>
                            <label class="template hint--top" data-slug="<?=$sSlugAttr?>" aria-label="<?=htmlspecialchars($oTemplate->getDescription(), ENT_QUOTES, 'UTF-8')?>">
                                <?php

                                echo form_radio(
                                    'template',
                                    $sSlug,
                                    set_radio('template', $sSlug, $bIsSelected),
                                    'data-revealer="cms-page-template"'
                                );

                                echo '<span class="icon">';
                                if (!empty($oTemplate->getIcon())) {
                                    echo img($oTemplate->getIcon());
                                }
                                echo '</span>';

                                ?>
                                <span class="name">
                                    <span><?=htmlspecialchars($oTemplate->getLabel(), ENT_QUOTES, 'UTF-8')?></span>
                                </span>
                                <span class="checkmark fa fa-check-circle"></span>
                            </label>
                        </li>
                        <?php
                    }
                }

                ?>
            </ul>
        </fieldset>
        <fieldset class="template-areas">
            <legend>Page content</legend>
            <div class="template-areas__body">
            <?php

            if ($aSlugsWithoutAreas) {
                ?>
                <p class="alert alert-info" data-revealer="cms-page-template" data-reveal-on="<?=$sNoAreas?>"<?=$bShowNoAreas ? '' : ' style="display:none"'?>>
                    This template has no editable areas.
                </p>
                <?php
            }

            foreach ($templates as $oTemplateGroup) {
                foreach ($oTemplateGroup->getTemplates() as $oTemplate) {

                    $aWidgetAreas = $oTemplate->getWidgetAreas();

                    if (empty($aWidgetAreas)) {
                        continue;
                    }

                    $sSlug     = $oTemplate->getSlug();
                    $sSlugAttr = htmlspecialchars($sSlug, ENT_QUOTES, 'UTF-8');
                    $bShow     = $defaultTemplate === $sSlug;

                    echo '<div class="btn-group template-area" id="template-area-' . $sSlugAttr . '" role="group"';
                    echo ' data-revealer="cms-page-template" data-reveal-on="' . $sSlugAttr . '"';
                    echo $bShow ? '' : ' style="display:none"';
                    echo '>';

                    foreach ($aWidgetAreas as $sWidgetSlug => $oWidgetArea) {
                        echo '<button type="button" class="btn btn-default js-cms-page-area" disabled';
                        echo ' data-area="' . htmlspecialchars($sWidgetSlug, ENT_QUOTES, 'UTF-8') . '">';
                        echo '<span class="fa fa-pencil"></span> ';
                        echo htmlspecialchars($oWidgetArea->getTitle(), ENT_QUOTES, 'UTF-8');
                        echo '</button>';
                    }

                    echo '</div>';
                }
            }

            ?>
            <input type="hidden" name="template_data" id="template-data" value="<?=$sTemplateData?>" />
            </div>
        </fieldset>
        <fieldset class="template-options">
            <legend>Template options</legend>
            <div class="template-options__body">
            <?php

            if ($aSlugsWithoutOptions) {
                ?>
                <p class="alert alert-info" data-revealer="cms-page-template" data-reveal-on="<?=$sNoOptions?>"<?=$bShowNoOptions ? '' : ' style="display:none"'?>>
                    This template has no additional options.
                </p>
                <?php
            }

            $oTemplateOptions = $oDraft?->template_options;

            foreach ($templates as $oTemplateGroup) {
                foreach ($oTemplateGroup->getTemplates() as $oTemplate) {

                    $sTplSlug             = $oTemplate->getSlug();
                    $aTplAdditionalFields = $oTemplate->getAdditionalFields();

                    if (empty($aTplAdditionalFields)) {
                        continue;
                    }

                    $sSlugAttr = htmlspecialchars($sTplSlug, ENT_QUOTES, 'UTF-8');
                    $bShow     = $defaultTemplate === $sTplSlug;

                    ?>
                    <div class="additional-fields" data-revealer="cms-page-template" data-reveal-on="<?=$sSlugAttr?>"<?=$bShow ? '' : ' style="display:none"'?>>
                        <?php

                        foreach ($aTplAdditionalFields as $oField) {

                            $sFieldKey = $oField->getKey();

                            if (is_object($oTemplateOptions) && !empty($oTemplateOptions->{$sFieldKey})) {
                                $oField->setDefault($oTemplateOptions->{$sFieldKey});
                            }

                            $oField->setKey('template_options[' . $sTplSlug . '][' . $sFieldKey . ']');

                            $sType = 'form_field_' . $oField->getProperty('type');
                            if (function_exists($sType)) {
                                echo $sType($oField->toArray());
                            } else {
                                echo form_field($oField->toArray());
                            }
                        }

                        ?>
                    </div>
                    <?php
                }
            }

            ?>
            </div>
        </fieldset>
    <?php

    $sContentTab = ob_get_clean();
    ob_start();

    ?>
        <fieldset>
            <legend>Search engines</legend>
            <?php

            echo form_field([
                'key'         => 'seo_title',
                'label'       => 'Title',
                'default'     => html_entity_decode((string) ($oDraft->seo_title ?? ''), ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                'placeholder' => 'Shown in search results. Falls back to the page title.',
                'max_length'  => 150,
            ]);

            echo form_field([
                'key'         => 'seo_description',
                'label'       => 'Description',
                'default'     => html_entity_decode((string) ($oDraft->seo_description ?? ''), ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                'placeholder' => 'A short summary for search results. Aim for under 150 characters.',
                'tip'         => 'Search engines may use this when they list the page.',
                'max_length'  => 300,
            ]);

            echo form_field([
                'key'         => 'seo_keywords',
                'label'       => 'Keywords',
                'default'     => html_entity_decode((string) ($oDraft->seo_keywords ?? ''), ENT_COMPAT | ENT_HTML5, 'UTF-8'),
                'placeholder' => 'Comma separated. Ten keywords or fewer is plenty.',
                'max_length'  => 150,
            ]);

            echo form_field_cdn_object_picker([
                'key'     => 'seo_image_id',
                'label'   => 'Image',
                'default' => $oDraft->seo_image_id ?? null,
                'tip'     => 'Used when the page is shared. Cropped to 1200×630.',
            ]);

            ?>
        </fieldset>
    <?php

    $sSeoTab = ob_get_clean();

    echo Helper::tabs([
        ['label' => 'Details', 'content' => $sDetailsTab],
        ['label' => 'Content', 'content' => $sContentTab],
        ['label' => 'SEO', 'content' => $sSeoTab],
    ]);

    echo Helper::floatingControls([
        'unsaved_changes' => true,
        'save'            => ['enabled' => false],
        'notes'           => ['enabled' => false],
        'html'            => [
            'left'  => $sSave . $sPublish,
            'right' => $sPreview,
        ],
    ]);

    echo form_close();

    ?>
</div>
