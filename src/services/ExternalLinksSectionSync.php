<?php

namespace astuteo\pjeShared\services;

use Craft;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\Tip;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;

class ExternalLinksSectionSync
{

    const SECTION_HANDLE = 'externalLinks';

    public function sync() {
        return (
            $this->_createSection() &&
            $this->_createFieldGroup() &&
            $this->_createFields() &&
            $this->_createFieldLayout() &&
            $this->_createSection() // resave to catch project config
        );
    }

    private function _createSection() : bool {
        $section = Craft::$app->sections->getSectionByHandle(self::SECTION_HANDLE);

        if(!$section) {
            $section = new Section([
                'name' => 'External Links',
                'handle' => self::SECTION_HANDLE,
                'type' => Section::TYPE_CHANNEL,
            ]);
        }
        $section->siteSettings = [
            new Section_SiteSettings([
                'siteId' => Craft::$app->sites->getPrimarySite()->id,
                'enabledByDefault' => true,
                'hasUrls' => true,
                'uriFormat' => '{slug}',
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

        $siteUrl = Craft::$app->fields->getFieldByHandle("siteUrl");
        if(!$siteUrl) {
            $siteUrl = new \craft\fields\PlainText([
                "handle" => "siteUrl",
                "groupId" => $group['id'],
            ]);
        }
        $siteUrl->name = "URL";
        $siteUrl->required = true;
//        $siteUrl->multiline = false;
//        $siteUrl->initialRows = 1;


        $newWindow = Craft::$app->fields->getFieldByHandle("newWindow");
        if(!$newWindow) {
            $newWindow = new \craft\fields\Lightswitch([
                "handle" => "newWindow",
                "groupId" => $group['id'],
            ]);
        }
        $newWindow->name = "New Window";

        $warnModal = Craft::$app->fields->getFieldByHandle("warnModal");
        if(!$warnModal) {
            $warnModal = new \craft\fields\Lightswitch([
                "handle" => "warnModal",
                "groupId" => $group['id'],
            ]);
        }
        $warnModal->name = "Warn Modal";


        $navigationLabel = Craft::$app->fields->getFieldByHandle("navigationLabel");
        if(!$navigationLabel) {
            $navigationLabel = new \craft\fields\PlainText([
                "handle" => "navigationLabel",
                "groupId" => $group['id'],
            ]);
        }
        $navigationLabel->name = "Navigation Label";
        $navigationLabel->instructions = 'If the label should be different than the page title, this field will override it.';
//        $noticeType->multiline = false;
//        $noticeType->initialRows = 1;


        $shortDescription = Craft::$app->fields->getFieldByHandle("shortDescription");
        if(!$shortDescription) {
            $shortDescription = new \craft\fields\PlainText([
                "handle" => "shortDescription",
                "groupId" => $group['id'],
            ]);
        }
        $shortDescription->name = "Short Description";
        $shortDescription->instructions = '15-20 words. Used in navigation areas to clarify links.';
//        $noticeType->multiline = false;
//        $noticeType->initialRows = 1;

        return (
            Craft::$app->fields->saveField($siteUrl) &&
            Craft::$app->fields->saveField($newWindow) &&
            Craft::$app->fields->saveField($warnModal) &&
            Craft::$app->fields->saveField($navigationLabel) &&
            Craft::$app->fields->saveField($shortDescription)
        );
    }

    private function _createFieldLayout() : bool {
        /*
         * @link: https://craftcms.stackexchange.com/questions/39497/craft-4-how-to-programmatically-attach-fields-to-entry-type-tab
         */
        $siteUrl = Craft::$app->fields->getFieldByHandle('siteUrl');
        $newWindow = Craft::$app->fields->getFieldByHandle('newWindow');
        $warnModal = Craft::$app->fields->getFieldByHandle('warnModal');
        $navigationLabel = Craft::$app->fields->getFieldByHandle('navigationLabel');
        $shortDescription = Craft::$app->fields->getFieldByHandle('shortDescription');

        $section = Craft::$app->sections->getSectionByHandle(self::SECTION_HANDLE);
        $entryTypes = Craft::$app->sections->getEntryTypesBySectionId($section->id);
        $entryType = $entryTypes[0];

        $layout = $entryType->getFieldLayout();
        $tabs = $layout->getTabs();

        $elements = [
            [
                'type' => CustomField::class,
                'fieldUid' => $siteUrl->uid,
                'required' => true,
                'width' => 50
            ],[
                'type' => CustomField::class,
                'fieldUid' => $newWindow->uid,
                'required' => true,
                'width' => 25
            ],[
                'type' => CustomField::class,
                'fieldUid' => $warnModal->uid,
                'required' => false,
                'width' => 25
            ],[
                'type' => CustomField::class,
                'fieldUid' => $navigationLabel->uid,
                'required' => false,
            ],[
                'type' => CustomField::class,
                'fieldUid' => $shortDescription->uid,
                'required' => false,
            ],
        ];

        $tabs[0]->setElements($elements);
        $layout->setTabs($tabs);
        return (Craft::$app->fields->saveLayout($layout));
    }


}
