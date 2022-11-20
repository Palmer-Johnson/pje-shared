<?php

namespace astuteo\pjeShared\helpers;


use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;


class HelpersService extends Component
{
    public static function clean($string) : string {
        return trim($string);
    }

    public static function now() : string {
        $now = DateTimeHelper::currentTimeStamp();
        return DateTimeHelper::toIso8601($now);
    }

    public static function log($message) : void {
        $file = Craft::getAlias('@storage/logs/pje-shared.log');
        $log = date('Y-m-d H:i:s').' '. print_r($message, true)."\n";
        \craft\helpers\FileHelper::writeToFile($file, $log, ['append' => true]);
    }
}
