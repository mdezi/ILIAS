<?php

declare(strict_types=1);

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
 * @author            Michael Jansen <mjansen@databay.de>
 * @ilCtrl_Calls      ilObjTermsOfServiceGUI: ilPermissionGUI
 * @ilCtrl_Calls      ilObjTermsOfServiceGUI: ilTermsOfServiceDocumentGUI
 * @ilCtrl_Calls      ilObjTermsOfServiceGUI: ilTermsOfServiceAcceptanceHistoryGUI
 * @ilCtrl_isCalledBy ilObjTermsOfServiceGUI: ilAdministrationGUI
 */
class ilObjTermsOfServiceGUI extends ilObject2GUI implements ilTermsOfServiceControllerEnabled
{
    protected ILIAS\DI\Container $dic;
    protected ilErrorHandling $error;

    /**
     * @inheritdoc
     */
    public function __construct($a_id = 0, $a_id_type = self::REPOSITORY_NODE_ID, $a_parent_node_id = 0)
    {
        global $DIC;

        $this->dic = $DIC;
        $this->lng = $DIC->language();
        $this->error = $DIC['ilErr'];

        parent::__construct($a_id, $a_id_type, $a_parent_node_id);

        $this->lng->loadLanguageModule('tos');
        $this->lng->loadLanguageModule('meta');
    }

    public function getType(): string
    {
        return 'tos';
    }

    public function executeCommand(): void
    {
        $this->prepareOutput();

        $nextClass = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd();

        $tableDataProviderFactory = new ilTermsOfServiceTableDataProviderFactory();
        $tableDataProviderFactory->setDatabaseAdapter($this->dic->database());

        switch (strtolower($nextClass)) {
            case strtolower(ilTermsOfServiceDocumentGUI::class):
                /** @var ilObjTermsOfService $obj */
                $obj = $this->object;
                $documentGui = new ilTermsOfServiceDocumentGUI(
                    $obj,
                    $this->dic['tos.criteria.type.factory'],
                    $this->dic->ui()->mainTemplate(),
                    $this->dic->user(),
                    $this->dic->ctrl(),
                    $this->dic->language(),
                    $this->dic->rbac()->system(),
                    $this->dic['ilErr'],
                    $this->dic->logger()->tos(),
                    $this->dic->toolbar(),
                    $this->dic->http(),
                    $this->dic->ui()->factory(),
                    $this->dic->ui()->renderer(),
                    $this->dic->filesystem(),
                    $this->dic->upload(),
                    $tableDataProviderFactory,
                    new ilTermsOfServiceTrimmedDocumentPurifier(new ilTermsOfServiceDocumentHtmlPurifier()),
                    $this->dic->refinery()
                );
                $this->ctrl->forwardCommand($documentGui);
                break;

            case strtolower(ilTermsOfServiceAcceptanceHistoryGUI::class):
                /** @var ilObjTermsOfService $obj */
                $obj = $this->object;
                $documentGui = new ilTermsOfServiceAcceptanceHistoryGUI(
                    $obj,
                    $this->dic['tos.criteria.type.factory'],
                    $this->dic->ui()->mainTemplate(),
                    $this->dic->ctrl(),
                    $this->dic->language(),
                    $this->dic->rbac()->system(),
                    $this->dic['ilErr'],
                    $this->dic->http(),
                    $this->dic->refinery(),
                    $this->dic->ui()->factory(),
                    $this->dic->ui()->renderer(),
                    $tableDataProviderFactory,
                );
                $this->ctrl->forwardCommand($documentGui);
                break;

            case strtolower(ilPermissionGUI::class):
                $perm_gui = new ilPermissionGUI($this);
                $this->ctrl->forwardCommand($perm_gui);
                break;

            default:
                if ($cmd === null || $cmd === '' || $cmd === 'view' || !method_exists($this, $cmd)) {
                    $cmd = 'settings';
                }
                $this->$cmd();
                break;
        }
    }

    public function getAdminTabs(): void
    {
        if ($this->rbac_system->checkAccess('read', $this->object->getRefId())) {
            $this->tabs_gui->addTarget(
                'tos_agreement_documents_tab_label',
                $this->ctrl->getLinkTargetByClass(ilTermsOfServiceDocumentGUI::class),
                '',
                [strtolower(ilTermsOfServiceDocumentGUI::class)]
            );
        }

        if ($this->rbac_system->checkAccess('read', $this->object->getRefId())) {
            $this->tabs_gui->addTarget(
                'settings',
                $this->ctrl->getLinkTarget($this, 'settings'),
                '',
                [strtolower(self::class)]
            );
        }

        if (
            (defined('USER_FOLDER_ID') && $this->rbac_system->checkAccess('read', USER_FOLDER_ID)) &&
            $this->rbac_system->checkAccess('read', $this->object->getRefId())
        ) {
            $this->tabs_gui->addTarget(
                'tos_acceptance_history',
                $this->ctrl->getLinkTargetByClass(ilTermsOfServiceAcceptanceHistoryGUI::class),
                '',
                [strtolower(ilTermsOfServiceAcceptanceHistoryGUI::class)]
            );
        }

        if ($this->rbac_system->checkAccess('edit_permission', $this->object->getRefId())) {
            $this->tabs_gui->addTarget(
                'perm_settings',
                $this->ctrl->getLinkTargetByClass([self::class, ilPermissionGUI::class], 'perm'),
                '',
                [strtolower(ilPermissionGUI::class), strtolower(ilObjectPermissionStatusGUI::class)]
            );
        }
    }

    protected function getSettingsForm(): \ILIAS\UI\Component\Input\Container\Form\Standard
    {
        /** @var ilObjTermsOfService $obj */
        $obj = $this->object;

        $fields = [];

        $fields['tos_status'] = $this->dic->ui()->factory()->input()->field()->optionalGroup(
            [
                'tos_reevaluate_on_login' => $this->dic->ui()->factory()->input()->field()->checkbox(
                    $this->lng->txt('tos_reevaluate_on_login'),
                    $this->lng->txt('tos_reevaluate_on_login_desc')
                )->withValue($obj->shouldReevaluateOnLogin())
                ->withDisabled(!$this->rbac_system->checkAccess('write', $this->object->getRefId()))
            ],
            $this->lng->txt('tos_status_enable'),
            $this->lng->txt('tos_status_desc')
        )->withValue(['tos_reevaluate_on_login' => false])
        ->withDisabled(!$this->rbac_system->checkAccess('write', $this->object->getRefId()));


        $form = $this->dic->ui()->factory()->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'saveSettings'),
            $fields
        );

        return $form;
    }

    protected function saveSettings(): void
    {
        if (!$this->rbac_system->checkAccess('write', $this->object->getRefId())) {
            $this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
        }

        $this->request = $this->dic->http()->request();

        $form = $this->getSettingsForm();
        $form = $form->withRequest($this->request);
        $data = $form->getData();

        $this->object->saveStatus((bool) $data['tos_status']);
        $this->object->setReevaluateOnLogin((bool) $data['tos_reevaluate_on_login']);

        $this->tpl->setOnScreenMessage('success', $this->lng->txt('saved_successfully'), true);

        $this->tpl->setContent($this->dic->ui()->renderer()->render($form));
    }

    protected function showMissingDocuments(): void
    {
        if ($this->object->getStatus()) {
            return;
        }

        if (0 === ilTermsOfServiceDocument::where([])->count()) {
            $this->components[] = $this->dic->ui()->factory()->messageBox()->info(
                $this->lng->txt('tos_no_documents_available')
            );
        }
    }

    protected function settings(): void
    {
        if (!$this->rbac_system->checkAccess('read', $this->object->getRefId())) {
            $this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
        }

        $this->showMissingDocuments();

        $form = $this->getSettingsForm();
        $this->tpl->setContent($this->dic->ui()->renderer()->render($form));
    }
}
