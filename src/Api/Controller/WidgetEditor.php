<?php

/**
 * Admin API for the WidgetEditor (v2)
 *
 * Served at api/cms/widgeteditor/… with no controller map.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Controller
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Api\Controller;

use Nails\Admin\Traits\Api\RestrictToAdmin;
use Nails\Api;
use Nails\Api\Exception\ApiException;
use Nails\Api\Factory\ApiResponse;
use Nails\Cms\Constants;
use Nails\Cms\WidgetEditor\Service\Catalogue;
use Nails\Cms\WidgetEditor\Service\Favourite;
use Nails\Cms\WidgetEditor\Service\Inspector;
use Nails\Cms\WidgetEditor\Service\Preview;
use Nails\Common\Service\HttpCodes;
use Nails\Factory;

class WidgetEditor extends Api\Controller\Base
{
    use RestrictToAdmin;

    const REQUIRE_AUTH = true;

    // --------------------------------------------------------------------------

    /**
     * Every non-disabled widget, plus groups, favourites and the area limits
     *
     * Pass the area rules as a JSON `rules` query parameter:
     * `{"allowed": ["richtext", "group:Text"], "disallowed": ["App.Cms.Widgets.Old"]}`.
     * Omit it to list everything the editor can place.
     *
     * @throws ApiException
     */
    public function getCatalogue(): ApiResponse
    {
        /** @var \Nails\Cms\Service\Widget $widgets */
        $widgets = Factory::service('Widget', Constants::MODULE_SLUG);

        return $this->respond($widgets->catalogue(
            $this->rules(),
            $this->favourites()->list($this->userId())
        ));
    }

    // --------------------------------------------------------------------------

    /**
     * Batch preview. Body: `{"context": {}, "items": [{"slug", "data"}]}`.
     *
     * @throws ApiException
     */
    public function postPreview(): ApiResponse
    {
        $body = $this->getRequestData();

        return $this->respond(
            (new Preview())->render(
                $this->registry(),
                $this->items($body, Preview::MAX_ITEMS),
                $this->context($body)
            )
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Batch HTML inspectors. Fields-schema widgets are answered without HTML.
     *
     * @throws ApiException
     */
    public function postInspector(): ApiResponse
    {
        $body = $this->getRequestData();

        return $this->respond(
            (new Inspector())->render(
                $this->registry(),
                $this->items($body, Inspector::MAX_ITEMS)
            )
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a favourite. Adding one that is already stored changes nothing.
     *
     * @throws ApiException
     */
    public function putFavourite(): ApiResponse
    {
        $favourites = $this->favourites();
        $favourites->add($this->userId(), $this->slug());

        return $this->respond(['favourites' => $favourites->list($this->userId())]);
    }

    // --------------------------------------------------------------------------

    /**
     * Removes a favourite. Removing one that is not stored changes nothing.
     *
     * @throws ApiException
     */
    public function deleteFavourite(): ApiResponse
    {
        $favourites = $this->favourites();
        $favourites->remove($this->userId(), $this->slug());

        return $this->respond(['favourites' => $favourites->list($this->userId())]);
    }

    // --------------------------------------------------------------------------

    /**
     * Replaces the user's favourites with the ordered list in `slugs`
     *
     * @throws ApiException
     */
    public function putFavourites(): ApiResponse
    {
        $body = $this->getRequestData();

        try {
            $slugs = Favourite::normaliseList($body['slugs'] ?? null);
        } catch (\InvalidArgumentException $e) {
            throw new ApiException($e->getMessage(), HttpCodes::STATUS_BAD_REQUEST);
        }

        $favourites = $this->favourites();
        $favourites->replace($this->userId(), $slugs);

        return $this->respond(['favourites' => $favourites->list($this->userId())]);
    }

    // --------------------------------------------------------------------------

    /**
     * @return array{allowed?: string[], disallowed?: string[]}
     * @throws ApiException
     */
    private function rules(): array
    {
        $raw = Factory::service('Input')->get('rules');
        if ($raw === null || $raw === '' || $raw === false) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw new ApiException('rules must be a JSON object', HttpCodes::STATUS_BAD_REQUEST);
        }

        $rules = [];
        foreach (['allowed', 'disallowed'] as $key) {
            if (!array_key_exists($key, $decoded)) {
                continue;
            }
            if (!is_array($decoded[$key]) || !array_is_list($decoded[$key])) {
                throw new ApiException($key . ' must be a list', HttpCodes::STATUS_BAD_REQUEST);
            }
            $rules[$key] = array_values(array_filter($decoded[$key], 'is_string'));
        }

        return $rules;
    }

    // --------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $body
     *
     * @return array<int, mixed>
     * @throws ApiException
     */
    private function items(array $body, int $max): array
    {
        $items = $body['items'] ?? null;
        if (!is_array($items) || !array_is_list($items)) {
            throw new ApiException('items must be a list', HttpCodes::STATUS_BAD_REQUEST);
        }
        if (count($items) > $max) {
            throw new ApiException(sprintf('At most %d items', $max), HttpCodes::STATUS_BAD_REQUEST);
        }

        return $items;
    }

    // --------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     * @throws ApiException
     */
    private function context(array $body): array
    {
        if (!array_key_exists('context', $body) || $body['context'] === null) {
            return [];
        }
        if (!is_array($body['context'])) {
            throw new ApiException('context must be an object', HttpCodes::STATUS_BAD_REQUEST);
        }

        return $body['context'];
    }

    // --------------------------------------------------------------------------

    /**
     * @throws ApiException
     */
    private function slug(): string
    {
        $slug = rawurldecode((string) Factory::service('Uri')->segment(5));

        try {
            Favourite::checkSlug($slug);
        } catch (\InvalidArgumentException $e) {
            throw new ApiException($e->getMessage(), HttpCodes::STATUS_BAD_REQUEST);
        }

        return $slug;
    }

    // --------------------------------------------------------------------------

    /**
     * @throws ApiException
     */
    private function userId(): int
    {
        $id = (int) activeUser('id');
        if ($id < 1) {
            throw new ApiException('You do not have permission to access this resource.', HttpCodes::STATUS_UNAUTHORIZED);
        }

        return $id;
    }

    // --------------------------------------------------------------------------

    private function favourites(): Favourite
    {
        return Factory::service('WidgetEditorFavourite', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    private function registry(): \Nails\Cms\WidgetEditor\Service\Registry
    {
        return Factory::service('Widget', Constants::MODULE_SLUG)->getRegistry();
    }

    // --------------------------------------------------------------------------

    private function respond(mixed $data): ApiResponse
    {
        return Factory::factory('ApiResponse', Api\Constants::MODULE_SLUG)->setData($data);
    }
}
