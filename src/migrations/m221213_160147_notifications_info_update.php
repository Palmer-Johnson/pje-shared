<?php

namespace astuteo\pjeShared\migrations;

use Craft;
use craft\db\Migration;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\Tip;

/**
 * m221213_160147_notifications_info_update migration.
 */
class m221213_160147_notifications_info_update extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        return (
            $this->_updateEntryTypeTip()
        );
    }



    private function _updateEntryTypeTip() : bool {
        /*
         * @link: https://craftcms.stackexchange.com/questions/39497/craft-4-how-to-programmatically-attach-fields-to-entry-type-tab
         */
        $noticeType = Craft::$app->fields->getFieldByHandle('noticeType');
        $noticeText = Craft::$app->fields->getFieldByHandle('noticeText');
        $noticeLink = Craft::$app->fields->getFieldByHandle('noticeLink');

        $section = Craft::$app->sections->getSectionByHandle('notifications');
        $entryTypes = Craft::$app->sections->getEntryTypesBySectionId($section->id);
        $entryType = $entryTypes[0];

        $layout = $entryType->getFieldLayout();
        $tabs = $layout->getTabs();

        $elements = [
            [
                'type' => Tip::class,
                'style' => 'tip',
                'tip' => '**Notes**:
                
- Active notifications will render at the top of the site. Toggle "Enabled" status in right column to hide notification
- If the notification is time sensitive use the "Post Date" and "Expiry Date" date settings in the right column.'
            ],
            [
                'type' => CustomField::class,
                'fieldUid' => $noticeType->uid,
                'required' => true,
                'width' => 100
            ],[
                'type' => CustomField::class,
                'fieldUid' => $noticeText->uid,
                'required' => true,
                'width' => 100
            ],[
                'type' => CustomField::class,
                'fieldUid' => $noticeLink->uid,
                'required' => false,
            ],
        ];

        $tabs[0]->setElements($elements);
        $layout->setTabs($tabs);
        return (Craft::$app->fields->saveLayout($layout));
    }


    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221213_160147_notifications_info_update cannot be reverted.\n";
        return false;
    }
}
