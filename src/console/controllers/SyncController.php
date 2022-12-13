<?php

namespace astuteo\pjeShared\console\controllers;

use astuteo\pjeShared\services\Notifications;
use astuteo\qa\services\InternalLinks;
use yii\console\Controller;

class SyncController extends Controller
{
    public function actionIndex() {
        InternalLinks::checkAll();
        return 'complete';
    }

    public function actionNotifications() {
        $CRAFT_ENVIRONMENT = defined('CRAFT_ENVIRONMENT') ? CRAFT_ENVIRONMENT : null;
        if($CRAFT_ENVIRONMENT !== 'dev') {
            echo "Can only be run in dev envoirment. Run there instead and use project.yaml files for production.\n";
            return;
        }
        (new Notifications)->syncNotification();
        return;
    }

}
