<?php

namespace Rahul\PasswordManager\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.0') < 0){
            $tableName = $installer->getTable('passwordhistory');
            if ($installer->getConnection()->isTableExists($tableName) != true) {
                $table = $installer->getConnection()
                    ->newTable($tableName)
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true
                        ],
                        'ID'
                    )
                    ->addColumn(
                        'user_id',
                        Table::TYPE_INTEGER,
                        10,
                        ['nullable' => false],
                        'User Id'
                    )
                    ->addColumn(
                        'email',
                        Table::TYPE_TEXT,
                        100,
                        ['nullable' => false],
                        'Email'
                    )
                    ->addColumn(
                        'password_hash',
                        Table::TYPE_TEXT,
                        200,
                        ['nullable' => false],
                        'Password Hash'
                    )
                    ->addColumn(
                        'store_id',
                        Table::TYPE_INTEGER,
                        5,
                        ['nullable' => false],
                        'Password Hash'
                    )
                    ->addColumn(
                        'created_at',
                        Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false,'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                        'Created At'
                    )
                    ->setComment('Password History Manager for store user')
                    ->setOption('type', 'InnoDB')
                    ->setOption('charset', 'utf8');
                $installer->getConnection()->createTable($table);
            }
		}

        $installer->endSetup();
    }
}