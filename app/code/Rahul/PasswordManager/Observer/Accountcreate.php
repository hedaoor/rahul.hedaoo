<?php

namespace Rahul\PasswordManager\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Rahul\PasswordManager\Helper\Data;

class Accountcreate implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Accountcreate constructor.
     * @param Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scope
     */
    public function __construct(
        Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scope
    ){
        $this->_helper = $helper;
        $this->_scopeConfig = $scope;
    }

    /**
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer){
        
        if($this->_helper->isEnable()){
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/customer_password_history.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            $customer = $observer->getEvent()->getCustomer();
            $logger->info('Accountcreate event called for '.$customer->getEmail());


            if($this->_helper->validatePasswordHistory($customer,'customer_register_success',$logger,null)->getId()){
                $logger->info("Password History created for ".$customer->getEmail()." in store #".$customer->getStoreId());
            }else{
                $logger->info("Password history not created for ".$customer->getEmail()." in store #".$customer->getStoreId());
            }

        }
    }
}

?>