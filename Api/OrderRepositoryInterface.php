<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Oxl\Delivery\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Oxl\Delivery\Api\Data\OrderInterface;
use Oxl\Delivery\Api\Data\OrderSearchResultsInterface;

/**
 * Order CRUD interface.
 * @api
 */
interface OrderRepositoryInterface
{
    /**
     * Save order.
     *
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function save(OrderInterface $order);

    /**
     * Retrieve order.
     *
     * @param int $orderId
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    public function getById($orderId);

    /**
     * Retrieve cities matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrderSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete order.
     *
     * @param OrderInterface $order
     * @return bool true on success
     */
    public function delete(OrderInterface $order);

    /**
     * Delete order by ID.
     *
     * @param int $orderId
     * @return bool true on success
     * @throws NoSuchEntityException
     */
    public function deleteById($orderId);
}
