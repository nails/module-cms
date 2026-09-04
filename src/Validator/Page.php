<?php

/**
 * Validates the admin create/edit page form
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Validator
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Validator;

use Nails\Common\Factory\Service\FormValidation\Validator;
use Nails\Common\Service\FormValidation;

class Page extends Validator
{
    protected function rules(): array
    {
        return [
            'title'              => [],
            'slug'               => [FormValidation::RULE_ALPHA_DASH],
            'parent_id'          => [FormValidation::RULE_IS_NATURAL],
            'template'           => ['trim', FormValidation::RULE_REQUIRED],
            'template_data'      => ['trim'],
            'template_options[]' => ['is_array'],
            'seo_title'          => ['trim', FormValidation::rule(FormValidation::RULE_MAX_LENGTH, 150)],
            'seo_description'    => ['trim', FormValidation::rule(FormValidation::RULE_MAX_LENGTH, 300)],
            'seo_keywords'       => ['trim', FormValidation::rule(FormValidation::RULE_MAX_LENGTH, 150)],
            'seo_image_id'       => [FormValidation::RULE_IS_NATURAL],
            'action'             => [FormValidation::RULE_REQUIRED],
        ];
    }

    // --------------------------------------------------------------------------

    protected function messages(): array
    {
        return [
            FormValidation::RULE_IS_NATURAL => 'Please select a valid Parent Page.',
            FormValidation::RULE_MAX_LENGTH => 'Exceeds maximum length ({param} characters)',
        ];
    }
}
