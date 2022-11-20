<?php

namespace astuteo\pjeShared\services;

class NormalizeLinks
{

    /*
     * @uses: https://plugins.craftcms.com/typedlinkfield
     */
    const EXTERNAL_SECTIONS = ['externalLinks'];
    const DEFAULT_LABEL = 'Learn More';

    public static function getLink($item) {
        return [
          'url' => self::getUrl($item),
          'title' => self::getTitle($item),
          'target' => self::getTarget($item),
          'type' => 'primary'
        ];
    }

    /*
     * Item can be one of the following:
     * - URL string
     * - Craft element
     * - Link field
     * - Twig Object
     */
    private static function getUrl($item) {
        /*
         * Entry or Category
         */
        $url = $item->url ?? false;
        if ($url) return $url;

        /*
         * Typed Link Field
         */
        if(!is_array($item) && method_exists($item,'getUrl')) {
            $url = $item->getUrl() ?? false;
        }
        if($url) return $url;

        if(is_array($item) && array_key_exists('url', $item)) {
            return $item['url'];
        }
        return $item;
    }

    private static function getTarget($item) {
        /*
         * If this is an entry that's not in the EXTERNAL_SECTIONS
         * sections array, default it to null otherwise set it to
         * blank. Otherwise, check for the setting key directly
         */
        $handle = $item->section->handle ?? false;
        if(!$handle) {
            $handle = $item->group->handle ?? false;
        }
        if($handle) {
            if( in_array($handle, self::EXTERNAL_SECTIONS)) {
                return '_blank';
            } else {
                return null;
            }
        }

        $target = false;
        /*
         * Typed Link Field
         */
        if(!is_array($item) && method_exists($item,'getTarget')) {
            $target = $item->getTarget() ?? false;
        }
        if($target) return $target;

        return $item['target'] ?? null;
    }

    private static function getTitle($item) {
        /*
         * Entry or Category
         */
        $title = $item->navigationLabel ?? $item->title ?? false;
        if($title) return $title;

        /*
         * Typed Link Field
         */
        if(!is_array($item) && method_exists($item,'getText')) {
            $title = $item->getText() ?? false;
        }
        if($title) return $title;

        return $item['title'] ?? self::DEFAULT_LABEL;
    }
}
