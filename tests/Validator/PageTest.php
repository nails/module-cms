<?php

namespace Tests\Validator;

use Nails\Cms\Validator\Page;
use Nails\Common\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    private function cleanPost(): array
    {
        return [
            'title'            => 'About us',
            'slug'             => 'about-us',
            'parent_id'        => '',
            'template'         => 'default',
            'template_data'    => '',
            'template_options' => ['default' => ['foo' => 'bar']],
            'seo_title'        => 'About',
            'seo_description'  => '',
            'seo_keywords'     => '',
            'seo_image_id'     => '',
            'action'           => 'PUBLISH',
        ];
    }

    private function errorsFor(array $aData): array
    {
        try {
            (new Page())->run($aData);
            return [];
        } catch (ValidationException $e) {
            return $e->getData();
        }
    }

    // --------------------------------------------------------------------------

    public function test_a_clean_page_passes(): void
    {
        self::assertSame([], $this->errorsFor($this->cleanPost()));
    }

    public function test_template_and_action_are_required(): void
    {
        self::assertSame(['template', 'action'], array_keys($this->errorsFor([])));
    }

    public function test_the_slug_may_only_contain_url_safe_characters(): void
    {
        $aErrors = $this->errorsFor(array_merge($this->cleanPost(), ['slug' => 'about us!']));
        self::assertSame(['slug'], array_keys($aErrors));
    }

    public function test_the_parent_must_be_a_natural_number_with_a_friendly_message(): void
    {
        $aErrors = $this->errorsFor(array_merge($this->cleanPost(), ['parent_id' => 'abc']));
        self::assertSame(['parent_id' => 'Please select a valid Parent Page.'], $aErrors);

        self::assertSame([], $this->errorsFor(array_merge($this->cleanPost(), ['parent_id' => '12'])));
    }

    public function test_seo_fields_report_their_maximum_length(): void
    {
        $aErrors = $this->errorsFor(array_merge($this->cleanPost(), [
            'seo_title'       => str_repeat('x', 151),
            'seo_description' => str_repeat('x', 301),
        ]));

        self::assertSame(
            [
                'seo_title'       => 'Exceeds maximum length (150 characters)',
                'seo_description' => 'Exceeds maximum length (300 characters)',
            ],
            $aErrors
        );
    }

    public function test_text_fields_are_trimmed_in_the_validated_data(): void
    {
        $oValidator = (new Page())->run(array_merge($this->cleanPost(), [
            'template'  => '  default  ',
            'seo_title' => "  About\n",
        ]));

        $aData = $oValidator->getValidatedData();

        self::assertSame('default', $aData['template']);
        self::assertSame('About', $aData['seo_title']);
        self::assertSame($this->cleanPost()['template_options'], $aData['template_options'], 'arrays pass through untouched');
    }
}
