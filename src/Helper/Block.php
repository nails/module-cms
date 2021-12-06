<?php

namespace Nails\Cms\Helper;

use Nails\Cms\Constants;
use Nails\Cms\Model;
use Nails\Cms\Resource;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Helper\Model\WhereIn;
use Nails\Factory;

/**
 * Class Block
 *
 * @package Nails\Cms\Helper
 */
class Block
{
    /**
     * @param string   $sInput The input string
     * @param string[] $aTags  Any additional tags to replace (key => value)
     *
     * @return string
     * @throws FactoryException
     */
    public static function replaceShortTags(string $sInput, array $aTags = []): string
    {
        preg_match_all('/\[:([a-zA-Z0-9\-]+?):\]/', $sInput, $aMatches);

        if (!empty($aMatches[1])) {
            $aTags = array_merge(
                $aTags,
                array_combine(
                    $aMatches[1],
                    array_pad([], count($aMatches[1]), null)
                )
            );
        }

        if (!empty($aTags)) {

            /** @var Model\Block $oBlockModel */
            $oBlockModel = Factory::model('Block', Constants::MODULE_SLUG);

            /** @var Resource\Block $aBlocks */
            $aBlocks = $oBlockModel->getBySlugs(array_keys($aTags));

            if ($aBlocks) {
                foreach ($aBlocks as $oBlock) {

                    if (array_key_exists($oBlock->slug, $aTags)) {

                        //  Translate some block types
                        switch ($oBlock->type) {
                            case Model\Block::TYPE_FILE:
                            case Model\Block::TYPE_IMAGE:
                                $oBlock->value = cdnServe($oBlock->value);
                                break;
                        }

                        $aTags[$oBlock->slug] = $oBlock->value;
                    }
                }
            }
        }

        foreach ($aTags as $sShortTag => $sValue) {
            $sInput = str_ireplace('[:' . $sShortTag . ':]', $sValue, $sInput);
        }

        return $sInput;
    }
}
