<?php

namespace Amitshree\Customer\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class SendApprovalEmail implements ObserverInterface
{
    protected $objectManager;
    protected $storeManager;

    public function __construct(
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        ObjectManager $objectManager,
        StoreManagerInterface $storeManager
    )
    {
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {

        /**@var $customer \Magento\Customer\Model\Data\Customer */
        $customer = $observer->getCustomerDataObject();
        $customerOld = $observer->getOrigCustomerDataObject();
        $approveAccount = (int)$customer->getCustomAttribute('approve_account')->getValue();
        $oldAppAcc = $customerOld->getCustomAttribute('approve_account');
        $approveAccountOld = isset($oldAppAcc) ? (int)$oldAppAcc->getValue() : 0;

        if ($approveAccount !== $approveAccountOld && $approveAccount === 1) {
            $store_id = $this->storeManager->getStore($customer->getStoreId())->getStoreGroupId();
            $template_email_id = $this->scopeConfig->getValue('customerlogin/general/account_approve_template', ScopeInterface::SCOPE_STORE, $store_id);
            $template_email =  $template_email_id ? $template_email_id :'amitshree_customer_account_approved';

            $firstName = $customer->getFirstName();
            $lastName = $customer->getLastname();

            $customerEmail = $customer->getEmail();
            $approveVariables = [
                'first_name' => $firstName,
                'last_name' => $lastName
            ];

            $email = $this->scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE);
            $name = $this->scopeConfig->getValue('trans_email/ident_general/name', ScopeInterface::SCOPE_STORE);


            $postObject = new \Magento\Framework\DataObject();
            $postObject->setData($approveVariables);

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($template_email)
                ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $customer->getStoreId()])
                ->setTemplateVars(['data' => $postObject])
                ->setFrom(['name' => $name, 'email' => $email])
                ->addTo([$customerEmail])
                ->getTransport();
            $transport->sendMessage();
        }
        return $this;
    }
}





