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

use Solspace\Freeform\Services\CrmService;
use Solspace\Freeform\Events\Integrations\PushEvent;

use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Events\Forms\ValidationEvent;

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
            // https://docs.solspace.com/craft/freeform/v4/developer/events/crm-integration/#before-data-is-pushed-to-the-crm
            Event::on(
                CrmService::class,
                CrmService::EVENT_BEFORE_PUSH,
                function (PushEvent $event) {

                    HelpersService::log('EVENT_BEFORE_PUSH');
                    $integration = $event->getIntegration();
                    $provider = $integration->getServiceProvider();
                    HelpersService::log($provider);
                    if ($provider === 'SalesforceLead') {
                        $values = $event->getValues();
                        $salesforceFieldName = Plugin::getInstance()->getSettings()->salesforceFieldName;
                        if (array_key_exists($salesforceFieldName, $values)) {
                            $updateMessage = ModifySubmissionMessage::add($values[$salesforceFieldName]);
                            $values[$salesforceFieldName] = $updateMessage;
                            $event->setValues($values);
                        }
                    }
                    HelpersService::log('UPDATE VALUES');
                    HelpersService::log($event->getValues());
                }
            );


            Event::on(
                SubmissionsService::class,
                SubmissionsService::EVENT_BEFORE_SUBMIT,
                function (SubmitEvent $event) {
                    HelpersService::log('EVENT_BEFORE_SUBMIT');
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
            Form::class,
            Form::EVENT_AFTER_VALIDATE,
            function (ValidationEvent $event) {
                $form = $event->getForm();



                HelpersService::log('EVENT_AFTER_VALIDATE');
                // do something here...
            }
        );



        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['_pje-shared'] = __DIR__ . '/templates/site';
                $event->roots['_pje-components'] = __DIR__ . '/templates/components';
            }
        );
    }
}
