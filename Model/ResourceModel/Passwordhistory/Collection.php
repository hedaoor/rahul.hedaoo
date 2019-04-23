<?php

namespace Rahul\PasswordManager\Model\ResourceModel\Passwordhistory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Rahul\PasswordManager\Model\Passwordhistory', 'Rahul\PasswordManager\Model\ResourceModel\Passwordhistory');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }

}
?>