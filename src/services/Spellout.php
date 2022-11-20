<?php

namespace astuteo\pjeShared\services;
use Craft;
use yii\base\Component;
use yii\base\InvalidConfigException;

class Spellout extends Component
{
    /**
     * @throws InvalidConfigException
     */
    public static function number($number): string | int
    {
        if(!is_numeric($number)) {
            return $number;
        }
        return Craft::$app->formatter->asSpellout( $number );
    }
}
