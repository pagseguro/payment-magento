<?php
namespace PagBank\PaymentMagento\Controller\Customer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use PagBank\PaymentMagento\Api\PagBankVaultManagementInterface;

class SaveCard extends Action
{
    /**
     * @var PagBankVaultManagementInterface
     */
    private $vaultManagement;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param Context $context
     * @param PagBankVaultManagementInterface $vaultManagement
     * @param JsonFactory $jsonFactory
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        PagBankVaultManagementInterface $vaultManagement,
        JsonFactory $jsonFactory,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->vaultManagement = $vaultManagement;
        $this->jsonFactory = $jsonFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $encryptedCard = $this->getRequest()->getParam('encrypted_card');
        $customerId = $this->customerSession->getCustomerId();

        try {
            $vaultToken = $this->vaultManagement->createVaultToken($customerId, $encryptedCard);
            return $this->jsonFactory->create()->setData([
                'success' => true,
                'message' => __('Card saved successfully.'),
                'token' => $vaultToken->getPagBankToken()
            ]);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
    }
}
