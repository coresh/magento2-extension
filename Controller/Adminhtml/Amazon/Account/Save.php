<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Save
 */
class Save extends Account
{
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $helperWizard;

    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Wizard $helperWizard,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperWizard = $helperWizard;
        $this->helperException = $helperException;
        $this->helperData = $helperData;
    }

    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
        }

        $id = $this->getRequest()->getParam('id');
        $data = $post->toArray();

        try {
            $account = $id ? $this->updateAccount($id, $data) : $this->addAccount($data);
        } catch (\Exception $exception) {
            $this->helperException->process($exception);

            $message = $this->__(
                'The Amazon access obtaining is currently unavailable.<br/>Reason: %error_message%',
                $exception->getMessage()
            );

            if ($this->isAjax()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $message
                ]);

                return $this->getResult();
            }

            $this->messageManager->addError($message);

            return $this->_redirect('*/amazon_account');
        }

        // Repricing
        // ---------------------------------------
        if (!empty($post['repricing']) && $account->getChildObject()->isRepricing()) {

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing $repricingModel */
            $repricingModel = $account->getChildObject()->getRepricing();

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Account_Repricing_SnapshotBuilder');
            $snapshotBuilder->setModel($repricingModel);

            $repricingOldData = $snapshotBuilder->getSnapshot();

            $this->modelFactory->getObject('Amazon_Account_Repricing_Builder')
                ->build($repricingModel, $post['repricing']);

            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Account_Repricing_SnapshotBuilder');
            $snapshotBuilder->setModel($repricingModel);

            $repricingNewData = $snapshotBuilder->getSnapshot();

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\Diff $diff */
            $diff = $this->modelFactory->getObject('Amazon_Account_Repricing_Diff');
            $diff->setOldSnapshot($repricingOldData);
            $diff->setNewSnapshot($repricingNewData);

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\AffectedListingsProducts $affectedListingsProducts */
            $affectedListingsProducts = $this->modelFactory->getObject(
                'Amazon_Account_Repricing_AffectedListingsProducts'
            );
            $affectedListingsProducts->setModel($repricingModel);

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\ChangeProcessor $changeProcessor */
            $changeProcessor = $this->modelFactory->getObject('Amazon_Account_Repricing_ChangeProcessor');
            $changeProcessor->process($diff, $affectedListingsProducts->getObjectsData(['id', 'status']));
        }
        // ---------------------------------------

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true
            ]);

            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Account was saved'));

        $routerParams = ['id' => $account->getId(), '_current' => true];
        if ($this->helperWizard->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK) &&
            $this->helperWizard->getStep(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK) == 'account') {
            $routerParams['wizard'] = true;
        }

        return $this->_redirect($this->helperData->getBackUrl('list', [], ['edit'=>$routerParams]));
    }

    //########################################
}
