<?php
namespace astuteo\pjeShared\console\controllers;
use astuteo\pjeShared\services\Breadcrumbs;
use yii\console\Controller;
use yii\db\StaleObjectException;


class DefaultController extends Controller
{
    /**
     * @throws StaleObjectException
     */
    public function actionFlushBreadcrumbs()
    {
        Breadcrumbs::deleteAllBreadcrumbs();
        return 'Breadcrumbs Deleted';
    }
}
