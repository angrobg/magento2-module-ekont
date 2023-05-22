<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Oxl\Delivery\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Zend_Db_Exception;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws Zend_Db_Exception
     */
    public function install(
        SchemaSetupInterface   $setup,
        ModuleContextInterface $context
    )
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Creating table oxl_ekont_order
         */
        $oxlEkontOrderTableName = 'oxl_ekont_order';

        if (!$installer->getConnection()->isTableExists($oxlEkontOrderTableName)) {
            $oxlEkontOrderTableName = $installer->getConnection()
                ->newTable($installer->getTable($oxlEkontOrderTableName))
                ->addColumn(
                    'ekont_order_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary' => true,
                    ]
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ]
                )
                ->addColumn(
                    'data',
                    Table::TYPE_TEXT,
                    null,
                    [
                        'nullable' => false,
                    ]
                )
                ->addColumn(
                    'date_created',
                    Table::TYPE_DATETIME,
                    null,
                    [
                        'nullable' => false,
                    ]
                )
                ->addIndex(
                    $installer->getIdxName(
                        $oxlEkontOrderTableName,
                        ['order_id'],
                        AdapterInterface::INDEX_TYPE_INDEX
                    ),
                    ['order_id'],
                    ['type' => AdapterInterface::INDEX_TYPE_INDEX]
                );

            $installer->getConnection()->createTable($oxlEkontOrderTableName);
        }

        $installer->endSetup();
    }
}
