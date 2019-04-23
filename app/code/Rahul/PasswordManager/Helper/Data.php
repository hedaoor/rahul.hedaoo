<?php

namespace Rahul\PasswordManager\Helper;

use Magento\Framework\App\Helper\Context;
use Rahul\PasswordManager\Model\PasswordhistoryFactory;
use Magento\Setup\Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;

class Data extends \Magento\Framework\App\Helper\AbstractHelper{
    /**
     *
     */
    const XML_PATH_MINIMUM_PASSWORD_LENGTH = 'customer/password/minimum_password_length';

    /**
     *
     */
    const XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER = 'customer/password/required_character_classes_number';

    /**
     *
     */
    const XML_PATH_STATUS = 'passwordhistory_section/general/enable';

    /**
     *
     */
    const XML_PATH_MAX_PASSWORD = 'passwordhistory_section/general/max_pass';

    /**
     *
     */
    const MAX_PASSWORD_LENGTH = 256;

    /**
     *
     */
    const MIN_PASSWORD_LENGTH = 6;

    /**
     * @var PasswordhistoryFactory
     */
    protected $_passhistory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerRepo;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $_StringHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    protected $_customerRegistry;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var bool
     */
    protected $flag;

    /**
     * @var \Zend\Log\Logger
     */
    protected $_logger;

    /**
     * @var
     */
    protected $_events;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Data constructor.
     * @param PasswordhistoryFactory $passwordhistoryFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Stdlib\StringUtils $StringHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scope
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
     */
	public function __construct(
        PasswordhistoryFactory $passwordhistoryFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Stdlib\StringUtils $StringHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scope,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession
    ){

        $this->_passhistory = $passwordhistoryFactory;
        $this->_customerRepo = $customerFactory;
        $this->_encryptor = $encryptor;
        $this->_StringHelper = $StringHelper;
        $this->_scopeConfig = $scope;
        $this->_customerRegistry = $customerRegistry;
        $this->customerRepository = $customerRepository;
        $this->_customerSession = $customerSession;
        $this->flag = false;

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/customer_password_history.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->_logger = $logger;

        $this->events = array('customer_account_edited','customer_account_reset_password');
    }

    /**
     * @return mixed
     */
    public function isEnable(){
        return $this->_scopeConfig->getValue(self::XML_PATH_STATUS,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getPasswordLimit(){
         return $this->_scopeConfig->getValue(self::XML_PATH_MAX_PASSWORD,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $customer_id
     * @param $password
     * @return bool
     * @throws \Exception
     */
    public function getUserPasswordList($customer_id,$password){

	    $passwordHistory = $this->_passhistory->create()->getCollection()
                                ->addFieldToFilter('user_id',$customer_id);
       
        $passwordHistory->setOrder('id','DESC');
        $passwordHistory->getSelect()->limit($this->getPasswordLimit());
        if ( $passwordHistory->count() > 0) {
            
            foreach ($passwordHistory as $key => $value) {
                $this->flag = false;
                if($this->_encryptor->validateHash($password,$value->getPasswordHash())){
                    $this->flag = true;
                    break;
                }
            }
        }

        return $this->flag;
   }

    /**
     * @param $customer
     * @param $event
     * @param null $logger
     * @param null $input_password
     * @param null $password
     * @return \Abbottstore\Passhistory\Model\Passwordhistory|void
     * @throws \Exception
     */
    public function validatePasswordHistory($customer,$event,$logger = null,$input_password = null,$password = null){
        try{
            $this->_logger->info('Customer #'.$customer->getId().' in the validatePasswordHistory method.');
            
            if(!$this->getUserPasswordList($customer->getId(),$password)){

                $this->_logger->info('Customer #'.$customer->getId().' New password does not match with last n password. Entering new password in table');

                $data['user_id'] = $customer->getId();
                $data['email'] = $customer->getEmail();
                $data['store_id'] = $customer->getStoreId();

                if(!in_array($event, $this->events)){
                    $customerPasswordHast = $this->_customerRepo->create()->load($data['user_id'])->getPasswordHash();
                    $data['password_hash'] = $customerPasswordHast;
                }
                else{ 
                    $data['password_hash'] = $input_password;
                }
                
                $this->_logger->info(print_r($data,true));
                return $this->_passhistory->create()->setData($data)->save();
            }
            else{
                $this->_logger->info('Customer #'.$customer->getId().' New Password match with last n password. Skip entering the new password in table');

                return ;
            }
        }
	    catch (Exception $e){
            $this->_logger->info($e->getMessage());
            return;
        }
    }

    /**
     * @param $data
     * @param $event
     * @param null $customerId
     * @return bool
     * @throws InvalidEmailOrPasswordException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     * @throws \Magento\Framework\Exception\State\UserLockedException
     */
    public function changePassword($data,$event,$customerId = null){
        
        if($this->_customerSession->getCustomer()->getId()){
            $customer = $this->customerRepository->getById($this->_customerSession->getCustomer()->getId());
        }
        else{
            $customer = $this->customerRepository->getById($customerId);
        }

        $this->_logger->info('Customer #'.$customer->getId().' requested for password change from '.$event);
        
        $customerSecure = $this->_customerRegistry->retrieveSecureData($customer->getId());
        if(isset($data['current_password'])){
            try{
                $this->getAuthentication()->authenticate(
                    $customer->getId(),
                    $data['current_password']
                );  
                $this->_logger->info('Customer #'.$customer->getId().' is authorized customer with valid password.');

            }
            catch (InvalidEmailOrPasswordException $e) {
                $this->_logger->info('Customer #'.$customer->getId().' is not authorized custom with invalid password.');

                throw new InvalidEmailOrPasswordException(__('The password doesn\'t match this account.'));
            }
        }

        $this->_logger->info('Customer #'.$customer->getId().' Checking password stength');
        $this->checkPasswordStrength($data['password']);
        $password = $data['password']; 
        $this->_logger->info('Customer #'.$customer->getId().' creating password hash.');
        $passwordHash = $this->createPasswordHash($data['password']); 
        
        try{
            $this->_logger->info('Customer #'.$customer->getId().' Validation customer new password with last n stored password in db');

            $history = $this->validatePasswordHistory($customer,$event,null,$passwordHash,$password);
            if(!empty($history)){
                if($history->getId()){
                    
                    $customerSecure->setRpToken(null);
                    $customerSecure->setRpTokenCreatedAt(null);
                    $customerSecure->setPasswordHash($passwordHash);
                    $this->customerRepository->save($customer);

                    $this->_logger->info('Customer #'.$customer->getId().' New password saved in customer table');
                    return true;
                }
                else{
                    $this->_logger->info('Customer #'.$customer->getId().' Not able to store new password in password history table');
                    return false;
                }
            }
            else{
                $this->_logger->info('Customer #'.$customer->getId().' Not able to store new password in password history table');

                return false;
            }

        }catch(Exception $e){
            $this->_logger->info($e->getMessage());
            return false;
        }
    }

    /**
     * @param $password
     * @return string
     */
    public function createPasswordHash($password)
    {
        return $this->_encryptor->getHash($password, true);
    }

    /**
     * @param $password
     */
    public function checkPasswordStrength($password){
        $length = $this->_StringHelper->strlen($password);
        if ($length > self::MAX_PASSWORD_LENGTH) {
            throw new InputException(
                __(
                    'Please enter a password with at most %1 characters.',
                    self::MAX_PASSWORD_LENGTH
                )
            );
        }

        $configMinPasswordLength = $this->getMinPasswordLength();

        if ($length < $configMinPasswordLength) {
            throw new InputException(
                __(
                    'Please enter a password with at least %1 characters.',
                    $configMinPasswordLength
                )
            );
        }

        if ($this->_StringHelper->strlen(trim($password)) != $length) {
            throw new InputException(__('The password can\'t begin or end with a space.'));
        }

        $requiredCharactersCheck = $this->makeRequiredCharactersCheck($password);
        if ($requiredCharactersCheck !== 0) {
            throw new InputException(
                __(
                    'Minimum of different classes of characters in password is %1.' .
                    ' Classes of characters: Lower Case, Upper Case, Digits, Special Characters.',
                    $requiredCharactersCheck
                )
            );
        }
    }

    /**
     * @param $password
     * @return int|mixed
     */
    public function makeRequiredCharactersCheck($password)
    {
        $counter = 0;
        $requiredNumber = $this->_scopeConfig->getValue(self::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER);
        $return = 0;

        if (preg_match('/[0-9]+/', $password)) {
            $counter ++;
        }
        if (preg_match('/[A-Z]+/', $password)) {
            $counter ++;
        }
        if (preg_match('/[a-z]+/', $password)) {
            $counter ++;
        }
        if (preg_match('/[^a-zA-Z0-9]+/', $password)) {
            $counter ++;
        }

        if ($counter < $requiredNumber) {
            $return = $requiredNumber;
        }

        return $return;
    }

    /**
     * Retrieve minimum password length
     *
     * @return int
     */
    protected function getMinPasswordLength()
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_MINIMUM_PASSWORD_LENGTH);
    }

    /**
     * Get authentication
     *
     * @return AuthenticationInterface
     */
    private function getAuthentication()
    {
        $this->authentication = null;
        if (!($this->authentication instanceof AuthenticationInterface)) { 
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Customer\Model\AuthenticationInterface::class
            );
        } else { 
            return $this->authentication;
        }
    }

}