<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Observer\Adminhtml;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ConfigChangeObserver implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var WriterInterface
     */
    protected $_configWriter;

    /**
     * ConfigChangeObserver constructor.
     *
     * @param ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter
    ) {
        $this->_messageManager = $messageManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_configWriter = $configWriter;
    }

    /**
     * Execute observer method
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // Get event data
        $website = $observer->getEvent()->getWebsite();
        $store = $observer->getEvent()->getStore();
        $changedPaths = $observer->getEvent()->getChangedPaths();

        // Define paths for configuration values
        $testModePath = 'payment/billwerkplus_subscription/api_key_type';
        $liveKeyPath = 'payment/billwerkplus_subscription/private_key';

        // Get the current scope
        $scope = $store ? 'stores' : ($website ? 'websites' : 'default');
        $scopeId = $store ? $this->_storeManager->getStore($store)->getId() : ($website ? $this->_storeManager->getWebsite($website)->getId() : 0);

        // Get current configuration values
        $testMode = $this->_scopeConfig->getValue($testModePath, $scope, $scopeId);
        $liveKey = $this->_scopeConfig->getValue($liveKeyPath, $scope, $scopeId);

        // Check if configuration values have changed
        foreach ($changedPaths as $path) {
            if (strpos($path, 'payment/billwerkplus_subscription') !== false) {
                // Add your messages based on changes
                if ($path == $testModePath && $testMode == 0) {
                    $this->_messageManager->addComplexNoticeMessage(
                        'testModeEnabledNoticeMessage',
                        [
                            'link' => 'https://docs.frisbii.com/reference/account'
                        ]
                    );
                } elseif ($path == $testModePath && $testMode == 1) {
                    $this->_messageManager->addComplexNoticeMessage(
                        'testModeDisabledNoticeMessage',
                        [
                            'link' => 'https://docs.frisbii.com/reference/account'
                        ]
                    );
                } elseif ($path == $liveKeyPath) {
                    $this->_messageManager->addComplexNoticeMessage(
                        'liveKeyChangedNoticeMessage',
                        [
                            'link' => 'https://docs.frisbii.com/reference/account'
                        ]
                    );
                }
            }
        }

        $allowedPaymentPath = 'payment/billwerkplus_subscription/allowwed_payment';
        $allowedPayment = $this->_scopeConfig->getValue($allowedPaymentPath, $scope, $scopeId);
        if (strpos($allowedPayment, 'mobilepay_subscriptions') !== false) {
            $this->_messageManager->addWarningMessage(
                _('MobilePay Subscription has been discontinued following the merger of MobilePay and Vipps. Please switch to using Vipps MobilePay Recurring instead.')
            );
        }
    }
}
