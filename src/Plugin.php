<?php
namespace astuteo\pjeShared;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\twig\variables\CraftVariable;

use Craft;
use craft\web\View;
use yii\base\Event;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\elements\GlobalSet;
use craft\helpers\ElementHelper;
use craft\console\Application as ConsoleApplication;

use astuteo\pjeShared\services\Breadcrumbs;
use astuteo\pjeShared\variables\pjeSharedVariable;
use astuteo\pjeShared\twigextensions\Extension;

class Plugin extends \craft\base\Plugin
{
    public static string $plugin;
    public static $instance;
    public string $schemaVersion = '1.0.2';

    public function init()
    {
        parent::init();
        self::$instance = $this;

        if (Craft::$app->request->getIsSiteRequest()) {
            Craft::$app->view->registerTwigExtension(new Extension());
        }

        /*
         * Register our variable
         */
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $e) {
                /** @var CraftVariable $variable */
                $variable = $e->sender;
                $variable->set('pjeShared', pjeSharedVariable::class);
            }
        );

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'astuteo\pjeShared\console\controllers';
        }

        /*
         * Watch elements that could be part
         * of one of the navigations
         */
//        Event::on(
//            Entry::class,
//            Entry::EVENT_AFTER_SAVE,
//            function (ModelEvent $e) {
//                $entry = $e->sender;
//                if (ElementHelper::isDraftOrRevision($entry)) {
//                    return;
//                }
//                Breadcrumbs::checkIfInNav($entry);
//            }
//        );

        /*
         * Watch global saves and record entries that are
         * in our supertable & matrix combo
         */
        Event::on(
            GlobalSet::class,
            GlobalSet::EVENT_AFTER_SAVE,
            function (ModelEvent $e) {
                $entry = $e->sender;
                Breadcrumbs::handleRecordingNav($entry);
                return;
            }
        );

        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['_pje-shared'] = __DIR__ . '/templates/site';
            }
        );

    }
}
