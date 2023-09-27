<?php

namespace astuteo\pjeShared\services;
use astuteo\pjeShared\Plugin;
use Craft;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class ModifySubmissionMessage
{
    /**
     * @throws InvalidConfigException
     * @throws ErrorException
     * @throws Exception
     */
    public static function add($message): string
    {
        return $message . "  \n" . "Requested from page: " . Craft::$app->getRequest()->getUrl();
    }

    public function shouldModify($form): bool
    {
        $settings = Plugin::getInstance()?->getSettings()?->mergeUriSalesforce ?? false;

        return match($settings) {
            false => false,
            true => true,
            default => is_array($settings) && in_array($form->getHandle(), $settings)
        };
    }

}
