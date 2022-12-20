<?php
namespace astuteo\pjeShared\services;

use astuteo\pjeShared\records\BreadcrumbsRecords;
use yii\base\Component;
use craft\elements\Entry;
use craft\elements\Category;
use craft\helpers\ArrayHelper;
use craft\elements\GlobalSet;


use astuteo\pjeShared\helpers\HelpersService;
use yii\db\StaleObjectException;

class Breadcrumbs extends Component
{
    /**
     * @throws StaleObjectException
     */
    public static function deleteAllBreadcrumbs(): bool
    {
        $breadcrumbs = BreadcrumbsRecords::find()
            ->all();
        if(!$breadcrumbs) {
            return true;
        }
        foreach ($breadcrumbs as $breadcrumb) {
            $breadcrumb->delete();
        }
        return true;
    }

    public static function breadcrumbsFromEntry($entry, $includeHome = true) {
        if($entry == null) {
            return null;
        }
        $matchedInGlobal = null;
        $appendFromGlobal = null;
        $globalParent = null;
        $ancestors = $entry->ancestors->all();
        $ancestorsIds = array_map(fn($entry) => $entry->id,  $ancestors);
        foreach ($ancestorsIds as $ancestorId) {
            $record = self::getRecordsByEntryId($ancestorId);
            if($record) {
                $matchedInGlobal = $record;
            }
        }

        if(!$matchedInGlobal) {
            $globalParent = self::getParentFromGlobal($entry);
        }

        /*
         * If we have a match, let's format this in a consistent way
         * so that we can treat it similar to a normal entry on the
         * front end. IF this has a target entry, that will be easy
         */
//        if($matchedInGlobal) {
//            $entry = self::getElementById($matchedInGlobal['entryId']);
//            if(!$entry) {
//                var_dump('no entry!');
//                var_dump($matchedInGlobal['entryId']);
//                var_dump($entry->id);
//                var_dump($entry->url);
//            } else {
//                var_dump('entry');
//                var_dump($entry->id);
//                var_dump($entry->slug);
//
//            }
//
//            $appendFromGlobal = [
//              'title' => $matchedInGlobal['startEntryLabel'] ?? $entry->title,
//              'url' => $entry->url ?? '',
//              'uri' => $entry->uri ?? '',
//              'slug' => $entry->slug ?? '',
//              'id' => $entry->id ?? '',
//            ];
//        }

        if($globalParent) {
            $base = ArrayHelper::merge([$globalParent], $ancestors, [$entry]);
        } else {
            $base = ArrayHelper::merge($ancestors, [$entry]);
        }

        if($includeHome) {
            $homepage = Entry::find()->section('homepage')->one();
            if($homepage->id !== $entry->id) {
                return ArrayHelper::merge([$homepage], $base);
            }
        }
        return $base;
    }



    /*
     * Record any entries that are part of the
     * navigation
     */
    public static function handleRecordingNav($element) : array|bool {
        HelpersService::log('reaching here');
        if(!$element->navigation) {
            return true;
        }
        HelpersService::log('reaching here 2');
        if(get_class($element->navigation) !== 'verbb\supertable\elements\db\SuperTableBlockQuery') {
            return true;
        }
        HelpersService::log('we do have a nav item');
        self::processSuperTableNavigation($element->navigation);
        return true;
    }

    private static function getParentFromGlobal($entry) {
        $entryId = $entry->id;
        $record = self::getRecordsByEntryId($entryId);
        if(!$record) {
            return null;
        }

        $parent = self::getElementById($record['startEntryId']);
        if(!$parent) {
            return null;
        }
        // we resolved to the same entry, do not add to breadcrumbs
        if($parent->id === $entry->id) {
            return null;
        }
        return $parent;
    }
    private static function getElementById($id) {
        $entry = Entry::find()->id($id)->one() ?? null;
        if(!$entry) {
            $entry = Category::find()->id($id)->one() ?? null;
        }
        return $entry;
    }

    // on global save, we want to add each element to the
    // pjeshared_breadcrumbs table, remove anything that
    // may be missing, and record its relationship with the
    // field handle, the parent entry ID or label and make
    // sure that is findable for our breadcrumbs add-on

    /*
     * Directly passes the instance of the field to be parsed
     * out
     */
    /**
     * @throws StaleObjectException
     */
    private static function processSuperTableNavigation($field) {
        /*
         * Store IDs on what we have on record to later remove
         * anything that is no longer part of the navigation
         */
        $recordsInField = self::getRecordsEntryIdsByFieldOwnerId($field->ownerId);
        $updatedRecordsInField = [];

        /*
         * Writes out parent and children records into the
         * breadcrumbs table for lookup later
         */

        // The field is a super table and row is super table row
        foreach ($field->all() as $row) {
            if($row->handleId) {
                $parentInfo = [
                    'startEntryId' => $row->entryTarget->one()->id ?? null,
                    'navigationHandle' => $row->handleId ?? null,
                    'startEntryLabel' => $row->label ?? null,
                    'fieldOwnerId' => $field->ownerId
                ];

                // matrix rows
                $matrix = $row->subnavItems;
                foreach ($matrix->all() as $block) {
                    $blockNav = Navigation::getMatrixBlockChildren($block, 1);
                    if($blockNav) {
                        $children = $blockNav['pages'];
                        if($blockNav['type'] === 'section' || $blockNav['type'] === 'pages') {
                            foreach ($children as $child) {
                                $updatedRecordsInField[] = $child->id;
                                self::saveBreadcrumbRecord($child->id, $parentInfo);
                            }
                        }
                    }
                }
            }
        }

        /*
         * Let's do some cleanup to make sure we aren't letting stragglers
         * behind in the navigation
         */

        $diff = array_diff($recordsInField, $updatedRecordsInField);

        foreach ($diff as $item) {
            self::removeBreadcrumbRecordByEntryId($item, $field->ownerId);
        }
    }

    /*
     * Find the records that are part of this instance of the field
     * by the owner ID (likely global set in this case)
     */
    private static function getRecordsByFieldOwnerId($id) {
        return BreadcrumbsRecords::find()
            ->where(['fieldOwnerId' => $id ])
            ->all();
    }

    private static function getRecordsEntryIdsByFieldOwnerId($id) {
        $records = self::getRecordsByFieldOwnerId($id);
        return array_map(fn($post) => $post->entryId,  $records);
    }

    private static function getRecordsByEntryId($id) {
        $records = BreadcrumbsRecords::find()
            ->where(['entryId'=> $id])
            ->all();


        if(count($records) == 0) {
            return null;
        }
        if(count($records) == 1 ) {
            return $records[0];
        }
        /*
         * If there are a lot of cases where there are more than one
         * match we may eventually want to try and match this based
         * on context of the page (likely url segment(s))
         */
        return $records[0];
    }


    private static function saveBreadcrumbRecord($entryId, $parent) {
        $fieldOwnerId = $parent['fieldOwnerId'];

        $record = BreadcrumbsRecords::find()
            ->where(['entryId' => $entryId, 'fieldOwnerId' => $fieldOwnerId])
            ->one();

        if(!$record) {
            $record = new BreadcrumbsRecords();
        }
        $record->entryId = $entryId;
        $record->navigationHandle = $parent['navigationHandle'];
        $record->startEntryId = $parent['startEntryId'];
        $record->fieldOwnerId = $parent['fieldOwnerId'];
        $record->startEntryLabel = $parent['startEntryLabel'];
        $record->save();
        return true;
    }

    /**
     * @throws StaleObjectException
     */
    private static function removeBreadcrumbRecordByEntryId($entryId, $fieldOwnerId) {
        $records = BreadcrumbsRecords::find()
            ->where(['entryId' => $entryId, 'fieldOwnerId' => $fieldOwnerId])
            ->all();
        if(!$records) {
            return true;
        }
        foreach ($records as $record) {
            $record->delete();
        }
        return true;
    }


}
