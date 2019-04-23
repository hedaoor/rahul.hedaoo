<?php
namespace Rahul\PasswordManager\Model\ResourceModel;

class Passwordhistory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('passwordhistory', 'id');
    }
}
?>