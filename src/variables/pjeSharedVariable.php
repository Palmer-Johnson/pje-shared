<?php
namespace astuteo\pjeShared\variables;

use astuteo\pjeShared\services\Breadcrumbs;
use astuteo\pjeShared\services\GooglePlaces;
use astuteo\pjeShared\services\Navigation;
use astuteo\pjeShared\services\NormalizeLinks;
use astuteo\pjeShared\services\PhoneHelper;

use craft\helpers\Json;

class pjeSharedVariable
{
    public function getNavigationFromSuperTable($table) : array|null|bool
    {
        return Navigation::getNavigationFromSuperTable($table);
    }

    public function isActive($uri) : bool {
        return Navigation::isActive($uri);
    }

    public function breadcrumbsFromEntry($entry) {
        return Breadcrumbs::breadcrumbsFromEntry($entry);
    }

    public function phoneArray($number) {
        return PhoneHelper::getPhoneArray($number);
    }

    public function googlePlaces($gid) {
        return GooglePlaces::getInfo($gid);
    }
    public function googlePlacesJson($gid) {
        return Json::encode(GooglePlaces::getInfo($gid));
    }

    public function normalizeLink($item) {
        return NormalizeLinks::getLink($item);
    }
}
