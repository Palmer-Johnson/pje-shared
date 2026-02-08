<?php

namespace astuteo\pjeShared\services;

use craft\fields\data\LinkData;

class NormalizeLinks
{

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
     * - Native Link field (LinkData)
     * - Twig Object
     */
    private static function getUrl($item) {
        if ($item instanceof LinkData) {
            return $item->getUrl() ?? false;
        }

        $url = $item->url ?? false;
        if ($url) return $url;

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
        if ($item instanceof LinkData) {
            return $item->target;
        }

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
        if(!is_array($item) && method_exists($item,'getTarget')) {
            $target = $item->getTarget() ?? false;
        }
        if($target) return $target;

        return $item['target'] ?? null;
    }

    private static function getTitle($item) {
        if ($item instanceof LinkData) {
            return $item->getLabel() ?? self::DEFAULT_LABEL;
        }

        $title = $item->navigationLabel ?? $item->title ?? false;
        if($title) return $title;

        if(!is_array($item) && method_exists($item,'getText')) {
            $title = $item->getText() ?? false;
        }
        if($title) return $title;

        return $item['title'] ?? self::DEFAULT_LABEL;
    }
}
