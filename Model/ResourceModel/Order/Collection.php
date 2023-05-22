<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Oxl\Delivery\Model\ResourceModel\Order;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init(
            'Oxl\Delivery\Model\Order',
            'Oxl\Delivery\Model\ResourceModel\Order'
        );
    }

    /**
     * @return \Oxl\Delivery\Model\ResourceModel\Order\Collection
     */
    public function addSalesOrder()
    {
        $this->getSelect()
            ->joinInner(
                ['sog' => $this->getTable('sales_order_grid')],
                'main_table.order_id = sog.entity_id',
                [
                    'increment_id',
                    'shipping_name',
                    'billing_name',
                    'status',
                ]
            );
        return $this;
    }
}
