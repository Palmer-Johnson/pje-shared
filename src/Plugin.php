<?php
namespace astuteo\pjeShared;

use astuteo\pjeShared\helpers\HelpersService;
use astuteo\pjeShared\models\Settings;
use astuteo\pjeShared\services\ModifySubmissionMessage;
use craft\events\RegisterTemplateRootsEvent;
use craft\log\MonologTarget;
use craft\web\twig\variables\CraftVariable;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;

use Craft;
use craft\web\View;
use yii\base\Event;
use craft\events\ModelEvent;
use craft\elements\GlobalSet;
use craft\console\Application as ConsoleApplication;

use astuteo\pjeShared\services\Breadcrumbs;
use astuteo\pjeShared\variables\pjeSharedVariable;
use astuteo\pjeShared\twigextensions\Extension;

// Freeform 4.x imports (safe to import - checked via isFreeformInstalled())
use Solspace\Freeform\Services\SubmissionsService;
use Solspace\Freeform\Events\Submissions\SubmitEvent;
use Solspace\Freeform\Services\CrmService;
use Solspace\Freeform\Events\Integrations\PushEvent;

// Freeform 5.x classes are referenced by string to avoid fatal errors when v5 is not installed

class Plugin extends \craft\base\Plugin
{
    public static string $plugin;
    public static $instance;
    public string $schemaVersion = '1.0.2';

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new \astuteo\pjeShared\models\Settings();
    }

    /**
     * Detect if Freeform 5.x is installed (vs 4.x)
     * Uses string-based class check to avoid fatal errors when v5 is not installed
     */
    private function isFreeform5(): bool
    {
        // Freeform 5 uses a new Form class location
        return class_exists('Solspace\\Freeform\\Form\\Form');
    }

    /**
     * Check if any version of Freeform is installed
     */
    private function isFreeformInstalled(): bool
    {
        return class_exists(SubmissionsService::class);
    }

    private function logFreeform(string $message): void
    {
        if ($this->getSettings()->logFreeformEvents) {
            HelpersService::info($message);
        }
    }

    /**
     * Register Freeform event listeners with version-appropriate APIs
     */
    private function registerFreeformEvents(): void
    {
        if ($this->getSettings()->mergeUriSalesforce) {
            $this->registerCrmPushEvent();
            $this->registerSubmissionEvent();
        }
    }

    /**
     * Register CRM push event (different in Freeform 4 vs 5)
     */
    private function registerCrmPushEvent(): void
    {
        if ($this->isFreeform5()) {
            // Freeform 5.x: https://docs.solspace.com/craft/freeform/v5/developer/events/crm-integration/
            $crmInterface = 'Solspace\\Freeform\\Library\\Integrations\\Types\\CRM\\CRMIntegrationInterface';
            Event::on(
                $crmInterface,
                $crmInterface::EVENT_ON_PUSH,
                function ($event) {
                    $this->handleCrmPush($event);
                }
            );
        } else {
            // Freeform 4.x: https://docs.solspace.com/craft/freeform/v4/developer/events/crm-integration/
            Event::on(
                CrmService::class,
                CrmService::EVENT_BEFORE_PUSH,
                function (PushEvent $event) {
                    $this->handleCrmPush($event);
                }
            );
        }
    }

    /**
     * Handle CRM push event (shared logic for both versions)
     */
    private function handleCrmPush($event): void
    {
        $this->logFreeform('========================================');
        $this->logFreeform('PJE-SHARED: CRM PUSH EVENT TRIGGERED');
        $this->logFreeform('Freeform Version: ' . ($this->isFreeform5() ? '5.x' : '4.x'));
        $this->logFreeform('========================================');

        $integration = $event->getIntegration();

        // Detect Salesforce integration - method differs between versions
        if ($this->isFreeform5()) {
            $providerClass = get_class($integration);
            $integrationName = method_exists($integration, 'getName') ? $integration->getName() : '';
            $isSalesforce = str_contains($providerClass, 'Salesforce')
                         || str_contains(strtolower($integrationName), 'salesforce');
            $this->logFreeform('Integration: ' . $integrationName . ' (' . $providerClass . ')');
        } else {
            $provider = $integration->getServiceProvider();
            $isSalesforce = $provider === 'SalesforceLead';
            $this->logFreeform('Provider: ' . $provider);
        }

        $this->logFreeform('Is Salesforce: ' . ($isSalesforce ? 'YES' : 'NO'));

        if ($isSalesforce) {
            $values = $event->getValues();
            $salesforceFieldName = Plugin::getInstance()->getSettings()->salesforceFieldName;
            $this->logFreeform('Looking for field: ' . $salesforceFieldName);
            if (array_key_exists($salesforceFieldName, $values)) {
                $originalValue = $values[$salesforceFieldName];
                $updateMessage = ModifySubmissionMessage::add($originalValue);
                $values[$salesforceFieldName] = $updateMessage;
                $event->setValues($values);
                $this->logFreeform('Modified field value:');
                $this->logFreeform('  Original: ' . $originalValue);
                $this->logFreeform('  Modified: ' . $updateMessage);
            } else {
                $this->logFreeform('Field not found in values - no modification made');
            }
        }
    }

    /**
     * Register submission event (same API in both versions)
     */
    private function registerSubmissionEvent(): void
    {
        Event::on(
            SubmissionsService::class,
            SubmissionsService::EVENT_BEFORE_SUBMIT,
            function (SubmitEvent $event) {
                $form = $event->getForm();
                $formHandle = $form->getHandle();

                $this->logFreeform('========================================');
                $this->logFreeform('PJE-SHARED: SUBMISSION EVENT TRIGGERED');
                $this->logFreeform('Form: ' . $formHandle);
                $this->logFreeform('========================================');

                if (!(new services\ModifySubmissionMessage)->shouldModify($form)) {
                    $this->logFreeform('Form not in mergeUriSalesforce list - skipping');
                    return;
                }

                $submission = $event->getSubmission();
                $message = $form->getLayout()->getField('message')?->getValueAsString();
                if ($message === null) {
                    $this->logFreeform('No message field found - skipping');
                    return;
                }
                $modifiedMessage = ModifySubmissionMessage::add($message);
                $submission->setFormFieldValues(['message' => $modifiedMessage], false);
                $this->logFreeform('Modified message field:');
                $this->logFreeform('  Original: ' . $message);
                $this->logFreeform('  Modified: ' . $modifiedMessage);
            }
        );
    }

    private function registerLogTarget(): void
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'pje-shared',
            'categories' => ['pje-shared'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => true,
            'formatter' => new LineFormatter(
                format: "[%datetime%] %message%\n",
                dateFormat: 'Y-m-d H:i:s',
                allowInlineLineBreaks: true,
                ignoreEmptyContextAndExtra: true,
            ),
        ]);
    }

    public function init()
    {
        parent::init();
        self::$instance = $this;

        $this->registerLogTarget();

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

        // Only do form parts if Freeform is installed
        if ($this->isFreeformInstalled()) {
            $version = $this->isFreeform5() ? '5.x' : '4.x';
            $settings = $this->getSettings()->mergeUriSalesforce;
            $this->logFreeform('PJE-SHARED: Freeform ' . $version . ' detected, registering events');
            $this->logFreeform('PJE-SHARED: mergeUriSalesforce setting = ' . json_encode($settings));
            $this->registerFreeformEvents();
        }

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
