<?php
namespace astuteo\pjeShared;
use astuteo\pjeShared\helpers\HelpersService;
use astuteo\pjeShared\models\Settings;
use astuteo\pjeShared\services\ModifySubmissionMessage;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\twig\variables\CraftVariable;

use Craft;
use craft\web\View;
use Solspace\Freeform\Freeform;
use yii\base\Event;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\elements\GlobalSet;
use craft\helpers\ElementHelper;
use craft\console\Application as ConsoleApplication;

use astuteo\pjeShared\services\Breadcrumbs;
use astuteo\pjeShared\variables\pjeSharedVariable;
use astuteo\pjeShared\twigextensions\Extension;

use Solspace\Freeform\Services\SubmissionsService;
use Solspace\Freeform\Events\Submissions\SubmitEvent;

class Plugin extends \craft\base\Plugin
{
    public static string $plugin;
    public static $instance;
    public string $schemaVersion = '1.0.2';

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new \astuteo\pjeShared\models\Settings();
    }

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

        if($this->getSettings()->syncBreadcrumbs) {
            Event::on(
                GlobalSet::class,
                GlobalSet::EVENT_AFTER_SAVE,
                function (ModelEvent $e) {
                    $entry = $e->sender;
                    Breadcrumbs::handleRecordingNav($entry);
                    return;
                }
            );
        }
        if ($this->getSettings()->mergeUriSalesforce) {
            Event::on(
                SubmissionsService::class,
                SubmissionsService::EVENT_BEFORE_SUBMIT,
                function (SubmitEvent $event) {
                    $form = $event->getForm();
                    if (!(new services\ModifySubmissionMessage)->shouldModify($form)) {
                        return;
                    }
                    $submission = $event->getSubmission();
                    $message = $form->getLayout()->getFieldByHandle('message')?->getValueAsString();
                    if ($message === null) {
                        return;
                    }
                    $submission->setFormFieldValues(['message' => ModifySubmissionMessage::add($message)], false);
                }
            );
        }




        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['_pje-shared'] = __DIR__ . '/templates/site';
            }
        );
    }
}
