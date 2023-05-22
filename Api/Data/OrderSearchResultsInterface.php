<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Oxl\Delivery\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 */
interface OrderSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return OrderInterface[]
     */
    public function getItems();

    /**
     * @param OrderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
