<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Oxl\Delivery\Model;

use Magento\Framework\Model\AbstractModel;
use Oxl\Delivery\Api\Data\OrderInterface;

class Order extends AbstractModel implements OrderInterface
{
    protected function _construct()
    {
        $this->_init('Oxl\Delivery\Model\ResourceModel\Order');
    }

    /**
     * Get ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @return mixed
     */
    public function getEkontOrderId()
    {
        return $this->getData(self::EKONT_ORDER_ID);
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @return mixed
     */
    public function getDataColumn()
    {
        return $this->getData(self::DATA);
    }

    /**
     * @return mixed
     */
    public function getDateCreated()
    {
        return $this->getData(self::DATE_CREATED);
    }

    /**
     * Get request
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->getData(self::REQUEST);
    }

    /**
     * Get error
     *
     * @return string
     */
    public function getError()
    {
        return $this->getData(self::ERROR);
    }

    /**
     * Get is successful
     *
     * @return bool
     */
    public function getIsSuccessful()
    {
        return $this->getData(self::IS_SUCCESSFUL);
    }

    /**
     * Set ID
     *
     * @param int $id
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @param int $ekontOrderId
     * @return mixed
     */
    public function setEkontOrderId($ekontOrderId)
    {
        return $this->setData(self::EKONT_ORDER_ID, $ekontOrderId);
    }

    /**
     * @param int $orderId
     * @return mixed
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @param string $data
     * @return mixed
     */
    public function setDataColumn($data)
    {
        return $this->setData(self::DATA, $data);
    }

    /**
     * @param string $dateCreated
     * @return mixed
     */
    public function setDateCreated($dateCreated)
    {
        return $this->setData(self::DATE_CREATED, $dateCreated);
    }

    /**
     * Set request
     *
     * @param string $data
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setRequest($data)
    {
        return $this->setData(self::REQUEST, $data);
    }

    /**
     * Set error
     *
     * @param string $data
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setError($data)
    {
        return $this->setData(self::ERROR, $data);
    }

    /**
     * Set is successful
     *
     * @param bool $data
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setIsSuccessful($data)
    {
        return $this->setData(self::IS_SUCCESSFUL, $data);
    }
}
