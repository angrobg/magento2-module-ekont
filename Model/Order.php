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
}
