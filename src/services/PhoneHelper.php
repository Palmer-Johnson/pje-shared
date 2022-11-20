<?php
namespace astuteo\pjeShared\services;

use yii\base\Component;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;

use astuteo\pjeShared\helpers\HelpersService;

class PhoneHelper extends Component
{
    public static function getPhoneArray($phone) {
        $clean = preg_replace('/[^0-9]/','', $phone);

        $nextThree = null;
        $lastFour = null;
        if(strlen($clean) > 10) {
            $countryCode = substr($clean, 0, strlen($clean)-10);
            $areaCode = substr($clean, -10, 3);
            $nextThree = substr($clean, -7, 3);
            $lastFour = substr($clean, -4, 4);
        }
        else if(strlen($clean) == 10) {
            $areaCode = substr($clean, 0, 3);
            $nextThree = substr($clean, 3, 3);
            $lastFour = substr($clean, 6, 4);
        }
        else if(strlen($clean) == 7) {
            $nextThree = substr($clean, 0, 3);
            $lastFour = substr($clean, 3, 4);
        }

        if(!$nextThree || !$lastFour) {
            return $phone;
        }
        return [
            'countryCode' => $countryCode ?? null,
            'areaCode' => $areaCode ?? null,
            'nextThree' => $nextThree,
            'lastFour' => $lastFour,
        ];
    }
}

