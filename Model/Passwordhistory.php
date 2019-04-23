<?php
namespace Rahul\PasswordManager\Model;

class Passwordhistory extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Rahul\PasswordManager\Model\ResourceModel\Passwordhistory');
    }
}
?>