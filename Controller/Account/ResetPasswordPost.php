<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Rahul\PasswordManager\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\InputException;
use Rahul\PasswordManager\Helper\Data;

class ResetPasswordPost extends \Magento\Customer\Controller\AbstractAccount
{
    /** @var AccountManagementInterface */
    protected $accountManagement;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $accountManagement
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $accountManagement,
        CustomerRepositoryInterface $customerRepository,
        Data $pass_helper
    ) {
        $this->session = $customerSession;
        $this->accountManagement = $accountManagement;
        $this->customerRepository = $customerRepository;
        $this->_pass_helper = $pass_helper;
        parent::__construct($context);
    }

    /**
     * Reset forgotten password
     *
     * Used to handle data received from reset forgotten password form
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resetPasswordToken = (string)$this->getRequest()->getQuery('token');
        $customerId = (int)$this->getRequest()->getQuery('id');
        $password = (string)$this->getRequest()->getPost('password');
        $passwordConfirmation = (string)$this->getRequest()->getPost('password_confirmation');

        if ($password !== $passwordConfirmation) {
            $this->messageManager->addError(__("New Password and Confirm New Password values didn't match."));
            $resultRedirect->setPath('*/*/createPassword', ['id' => $customerId, 'token' => $resetPasswordToken]);
            return $resultRedirect;
        }
        if (iconv_strlen($password) <= 0) {
            $this->messageManager->addError(__('Please enter a new password.'));
            $resultRedirect->setPath('*/*/createPassword', ['id' => $customerId, 'token' => $resetPasswordToken]);
            return $resultRedirect;
        }



        try {
            
            if($this->_pass_helper->isEnable()){

                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/customer.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);

                $data['password'] = $password;
                $data['password_confirmation'] = $passwordConfirmation;
                $logger->info("before if with used password");
                    
                if(!$this->_pass_helper->changePassword($data,'customer_account_reset_password',$customerId)){

                    $this->messageManager->addError(__('New password cannot be the same as old password'));
                    $logger->info("inside if with used password");

                    $resultRedirect->setPath('*/*/createPassword', ['id' => $customerId, 'token' => $resetPasswordToken]);
                    return $resultRedirect;     
                }
                else{
                    $this->messageManager->addSuccess(__('Your password has been updated.'));
                    $resultRedirect->setPath('*/*/login');
                    return $resultRedirect;
                }
            }
            else{

                $customerEmail = $this->customerRepository->getById($customerId)->getEmail();
                $this->accountManagement->resetPassword($customerEmail, $resetPasswordToken, $password);
                $this->session->unsRpToken();
                $this->session->unsRpCustomerId();
                $this->messageManager->addSuccess(__('Your password has been updated.'));
                $resultRedirect->setPath('*/*/login');
                return $resultRedirect;
            }
        } catch (InputException $e) {
            $this->messageManager->addError($e->getMessage());
            foreach ($e->getErrors() as $error) {
                $this->messageManager->addError($error->getMessage());
            }
        } catch (\Exception $exception) {
            $this->messageManager->addError(__('Something went wrong while saving the new password.'));
        }
        $resultRedirect->setPath('*/*/createPassword', ['id' => $customerId, 'token' => $resetPasswordToken]);
        return $resultRedirect;
    }
}
