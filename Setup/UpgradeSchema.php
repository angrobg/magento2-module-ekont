<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Oxl\Delivery\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $oxlEkontOrderTableName = 'oxl_ekont_order';

        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '999.0.2', '<')) {
            $tbl = $installer->getTable($oxlEkontOrderTableName);

            $installer->getConnection()
                ->addColumn(
                    $tbl,
                    'request',
                    [
                        'type' => Table::TYPE_TEXT,
                        'nullable' => false,
                        'default' => '',
                        'comment' => 'Request log',
                    ]
                );

            $installer->getConnection()->addColumn(
                $tbl,
                'error',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Error log',
                ]
            );

            $installer->getConnection()->addColumn(
                $tbl,
                'is_successful',
                [
                    'type' => Table::TYPE_BOOLEAN,
                    'comment' => 'Successful or not',
                ]
            );
        }

        $installer->endSetup();
    }
}
