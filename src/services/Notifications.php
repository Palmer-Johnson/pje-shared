<?php
namespace astuteo\pjeShared\services;

use Craft;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\Tip;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;

use yii\base\Component;


class Notifications extends Component
{

    public function syncNotification() {
        return (
            $this->_createSection() &&
            $this->_createFieldGroup() &&
            $this->_createFields() &&
            $this->_createFieldLayout() &&
            $this->_updateEntryType() &&
            $this->_installPlugins()
        );
    }

    private function _createSection() : bool {
        $section = Craft::$app->sections->getSectionByHandle('notifications');

        if(!$section) {
            $section = new Section([
                'name' => 'Notifications',
                'handle' => 'notifications',
                'type' => Section::TYPE_CHANNEL,
            ]);
        }
        $section->siteSettings = [
            new Section_SiteSettings([
                'siteId' => Craft::$app->sites->getPrimarySite()->id,
                'enabledByDefault' => true,
                'hasUrls' => false,
                'uriFormat' => '',
                'template' => 'entry',
            ]),
        ];
        return Craft::$app->sections->saveSection($section);
    }

    private function _createFieldGroup() : bool {
        // Check if it is already there
        $group = (new \craft\db\Query())
            ->select("id")
            ->from("fieldgroups")
            ->where(["name" => "Components"])
            ->one();

        if(!$group) {
            $group = new \craft\models\FieldGroup([
                "name" => "Components",
            ]);
            // Save the group
            return Craft::$app->fields->saveGroup($group);
        }
        return true;
    }

    private function _createFields() : bool {
        $group = (new \craft\db\Query())
            ->select("id")
            ->from("fieldgroups")
            ->where(["name" => "Components"])
            ->one();

        // Add Notice Type Field
        $noticeType = Craft::$app->fields->getFieldByHandle("noticeType");
        if(!$noticeType) {
            $noticeType = new \craft\fields\PlainText([
                "handle" => "noticeType",
                "groupId" => $group['id'],
            ]);
        }
        $noticeType->name = "Notice: Type";
        $noticeType->instructions = "Appears to left of text (ex: News, Update, Reminder, Alert)";
        $noticeType->required = true;
        $noticeType->multiline = false;
        $noticeType->initialRows = 1;

        // Add Notice Text
        $noticeText = Craft::$app->fields->getFieldByHandle("noticeText");
        if(!$noticeText) {
            $noticeText = new \craft\fields\PlainText([
                "handle" => "noticeText",
                "groupId" => $group['id'],
            ]);
        }
        $noticeText->name = "Notice: Text";
        $noticeText->required = true;
        $noticeText->multiline = true;
        $noticeText->initialRows = 2;

        // Add Notice Link
        $noticeLink = Craft::$app->fields->getFieldByHandle("noticeLink");
        if(!$noticeLink) {
            $noticeLink = new \lenz\linkfield\fields\LinkField([
                "handle" => "noticeLink",
                "groupId" => $group['id'],
            ]);
        }
        $noticeLink->name = "Notice: Link";
        $noticeLink->allowTarget = true;
        $noticeLink->allowCustomText = true;

        return (
            Craft::$app->fields->saveField($noticeType) &&
            Craft::$app->fields->saveField($noticeText) &&
            Craft::$app->fields->saveField($noticeLink)
        );
    }

    private function _createFieldLayout() : bool {
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

    private function _updateEntryType() : bool {
        $section = Craft::$app->sections->getSectionByHandle('notifications');
        $entryTypes = Craft::$app->sections->getEntryTypesBySectionId($section->id);
        $entryType = $entryTypes[0];

        $entryType->hasTitleField = false;
        $entryType->titleFormat = '{noticeType}: {noticeText}';

        return (Craft::$app->sections->saveEntryType($entryType));

    }

    private function _installPlugins() {
        Craft::$app->plugins->installPlugin('wabisoft-components');
        Craft::$app->plugins->installPlugin('wabisoft-framework');
        return true;
    }
}
