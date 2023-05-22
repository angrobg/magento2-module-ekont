<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Oxl\Delivery\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Oxl\Delivery\Api\Data\OrderInterface;
use Oxl\Delivery\Api\Data\OrderInterfaceFactory;
use Oxl\Delivery\Api\Data\OrderSearchResultsInterfaceFactory;
use Oxl\Delivery\Api\OrderRepositoryInterface;
use Oxl\Delivery\Model\ResourceModel\Order as OrderResource;
use Oxl\Delivery\Model\ResourceModel\Order\CollectionFactory;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @var OrderResource
     */
    private OrderResource $orderResource;

    /**
     * @var OrderFactory
     */
    private OrderFactory $orderFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var OrderSearchResultsInterfaceFactory
     */
    private OrderSearchResultsInterfaceFactory $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected DataObjectHelper $dataObjectHelper;

    /**
     * @var OrderInterfaceFactory
     */
    protected OrderInterfaceFactory $orderInterfaceFactory;

    /**
     * OrderRepository constructor.
     * @param OrderResource $orderResource
     * @param OrderFactory $orderFactory
     * @param CollectionFactory $collectionFactory
     * @param OrderSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param OrderInterfaceFactory $orderInterfaceFactory
     */
    public function __construct(
        OrderResource                      $orderResource,
        OrderFactory                       $orderFactory,
        CollectionFactory                  $collectionFactory,
        OrderSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper                   $dataObjectHelper,
        OrderInterfaceFactory              $orderInterfaceFactory
    )
    {
        $this->orderResource = $orderResource;
        $this->orderFactory = $orderFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->orderInterfaceFactory = $orderInterfaceFactory;
    }

    /**
     * Save order data
     *
     * @param OrderInterface $order
     * @return int
     * @throws CouldNotSaveException
     */
    public function save(OrderInterface $order)
    {
        try {
            $this->orderResource->save($order);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Ekont order: %1',
                $exception->getMessage()
            ));
        }

        return $order->getId();
    }

    /**
     * Get Ekont order by ID
     *
     * @param int $orderId
     * @return Order
     * @throws NoSuchEntityException
     */
    public function getById($orderId)
    {
        $order = $this->orderFactory->create();

        $this->orderResource->load($order, $orderId, 'ekont_order_id');

        if (!$order->getId()) {
            throw new NoSuchEntityException(__('Ekont order with id "%1" does not exist.', $orderId));
        }

        return $order;
    }

    /**
     * Load Ekont order data collection by given search criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Customer\Api\Data\OrderSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        $collection = $this->collectionFactory->create();
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'store_id') {
                    $collection->addStoreFilter($filter->getValue(), false);
                    continue;
                }
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $orders = [];
        /** @var Order $orderModel */
        foreach ($collection as $orderModel) {
            $adddressData = $this->orderInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $adddressData,
                $orderModel->getData(),
                'Oxl\Delivery\Api\Data\OrderInterface'
            );
            $orders[] = $orderModel;
        }
        $searchResults->setItems($orders);
        return $searchResults;
    }

    /**
     * Delete order
     *
     * @param OrderInterface $order
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(OrderInterface $order)
    {
        try {
            $this->orderResource->delete($order);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Ekont order: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * Delete Ekont order by given ID
     *
     * @param int $orderId
     * @return bool
     */
    public function deleteById($orderId)
    {
        return $this->delete($this->getById($orderId));
    }
}
