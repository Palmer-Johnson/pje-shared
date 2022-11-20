<?php
namespace astuteo\pjeShared\services;

use craft\elements\Category;
use craft\helpers\Json;
use yii\base\Component;
use Craft;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\helpers\StringHelper;

class Navigation extends Component
{
    //
    // PUBLIC FUNCTIONS
    //
    /*
     * Loops through a super table field that is built for
     * navigation. The following fields are required to operate
     * correctly
     *
     * Label - plain text
     * Entry Target - optional entry that the top level item aliases
     * Subnav Items - Matrix field with the following block options:
     * |- Pages - related Entries
     * |- Entire Section - dropdown selection with the values as handles of sections
     * |- Manual Link: fullUrl <plaintext> and linkLabel <plaintext>
     */
    public static function getNavigationFromSuperTable($superTable, $includeChildren = true) {
        $nav = [];
        foreach ($superTable->with(['subnavItems','entryTarget'])->collect() as $row) {
            $content = self::processRow($row, $includeChildren);
            $nav[$content['navId']] = $content;
        }
        return $nav;
    }
    /*
     * Checks if the navigation uri is in the the current
     * request uri
     */
    public static function isActive($uri) : bool {
        $request = Craft::$app->request->segments;
        if($uri === '') {
            return false;
        }
        $requestUri = StringHelper::toString($request, '/');
        if(StringHelper::startsWith($requestUri, $uri)) {
            return true;
        }
        return false;
    }


    public static function mapNavItem($item, $subpages = null) : array {
        $title = $item->title ?? $item['title'];
        $slug = $item->slug ?? $item['slug'];
        $url = $item->url ?? $item['url'];
        $uri = $item->uri ?? $item['uri'];
        $id = $item->id ?? $item['id'] ?? null;
        $hasChildren = $item->hasChildren ?? $item['hasChildren'] ?? null;
        $children = $subpages ?? $item->hasChildren ?? $item['children'] ?? null;
        $element = !is_array($item) ? $item : null;
        return [
            'title' => $title,
            'slug' => $slug,
            'url' => $url,
            'uri' => $uri,
            'id' => $id,
            'hasChildren'=> $hasChildren,
            'children'=> $children,
            'element' => $element
        ];
    }


    //
    // PRIVATE FUNCTIONS
    //
    /*
     * Processes the table rows
     */
    private static function processRow($row, $includeChildren = true) {

        if($includeChildren) {
            $children =  self::getChildren($row);
            $hasChildren = count($children) > 0;
        }
        $topTarget = self::getTopTarget($row, $children ?? null);

        return [
            'title' => $row->label,
            'navId' => $row->handleId ?? $topTarget['slug'],
            'slug' => $topTarget['slug'],
            'url' => $topTarget['url'],
            'uri' => $topTarget['uri'],
            'id' => $topTarget['id'] ?? null,
            'hasChildren' => $hasChildren ?? false,
            'children' => $children ?? [],
        ];
    }

    /*
     * Gets the top target, and if it has an entry target
     * references that otherwise grab the first child of any
     * type.
     */
    private static function getTopTarget($row, $children = null) {
        $target = $row->entryTarget->one() ?? null;
        if($target) {
            return $target;
        }

        $values = [
            'slug' => '',
            'url' => '',
            'uri' => '',
            'id' => null
        ];
        if($children && $children[0]["pages"]) {
            $subpages = $children[0]["pages"];
            $firstPage = ArrayHelper::firstValue($subpages);
            $values = self::mapNavItem($firstPage);
        }
        return $values;
    }

    /*
     * get children based on matrix block type
     */
    private static function getChildren($parent) : array {
        $matrix = $parent->subnavItems;
        $children = [];
        foreach ($matrix->all() as $row) {
            $children[] = self::getMatrixBlockChildren($row);
        }
        return $children;
    }


    public static function getMatrixBlockChildren($row, $level = null) {
        $blockType = Craft::$app->getMatrix()->getBlockTypeById($row->typeId);
        $blockHandle = $blockType->handle;
        if($blockHandle === 'pages') {
            return self::getPages($row);
        }
        if($blockHandle === 'section') {
            return self::getSection($row, $level);
        }
        if($blockHandle === 'manualLink') {
            return self::getManual($row);
        }
        return null;
    }

    /*
     * Process Matrix block handle of "pages"
     */
    private static function getPages($row) : array {
        $subpages = $row->entry->all();
        return [
          'type' => 'pages',
          'matrixBlock' => $row,
          'pages' => $subpages
        ];
    }

    /*
     * Process Matrix block handle of "section"
     */
    private static function getSection($row, $level = 1) : array {
        $subpages = [];
        // check if handle for section
        if($row->selectSection) {
            $subpages = Entry::find()->section($row->selectSection)->level($level)->all();
        }
        // this could be a handle for categories
        if(count($subpages) < 1) {
            $subpages = Category::find()->group($row->selectSection)->level($level)->all();
        }
        return [
            'type' => 'section',
            'matrixBlock' => $row,
            'pages' => $subpages
        ];
    }

    /*
     * Process Matrix block handle of "manualLink"
     */
    private static function getManual($row) : array {
        $subpages = [
            'title' => $row->linkLabel,
            'url' => $row->fullUrl,
            'slug' => basename($row->fullUrl),
            'uri' => self::getUri($row->fullUrl),
            'external' => self::isExternal($row->fullUrl),
            'id' => null
        ];

        return [
            'type' => 'manual',
            'matrixBlock' => $row,
            'pages' => [$subpages]
        ];
    }



    private static function getUri($string) {
        $uri = UrlHelper::rootRelativeUrl($string);
        return StringHelper::removeLeft($uri, '/');
    }

    private static function isExternal($url) : bool  {
        $host = UrlHelper::host() ?? '';
        if(StringHelper::startsWith($url, $host, false)) {
            return true;
        }
        return false;
    }
}
