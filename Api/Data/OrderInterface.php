<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Oxl\Delivery\Api\Data;

/**
 * Ekont Order interface.
 * @api
 */
interface OrderInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ID = 'ekont_order_id';
    const EKONT_ORDER_ID = 'ekont_order_id';
    const ORDER_ID = 'order_id';
    const DATA = 'data';
    const DATE_CREATED = 'date_created';
    const REQUEST = 'request';
    const ERROR = 'error';
    const IS_SUCCESSFUL = 'is_successful';

    /**#@-*/

    /**
     * Get ID
     *
     * @return int
     */
    public function getId();

    /**
     * Get ekont order id
     *
     * @return int
     */
    public function getEkontOrderId();

    /**
     * Get order id
     *
     * @return int
     */
    public function getOrderId();

    /**
     * Get data
     *
     * @return string
     */
    public function getDataColumn();

    /**
     * Get date_created
     *
     * @return string
     */
    public function getDateCreated();

    /**
     * Get request
     *
     * @return string
     */
    public function getRequest();

    /**
     * Get error
     *
     * @return string
     */
    public function getError();

    /**
     * Get is successful
     *
     * @return bool
     */
    public function getIsSuccessful();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setId($id);

    /**
     * Set ekont order id
     *
     * @param int $ekontorderId
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setEkontOrderId($ekontorderId);

    /**
     * Set order id
     *
     * @param int $orderId
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setOrderId($orderId);

    /**
     * Set data
     *
     * @param string $data
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setDataColumn($data);

    /**
     * Set date created
     *
     * @param string $dateCreated
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setDateCreated($dateCreated);

    /**
     * Set request
     *
     * @param string $data
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setRequest($data);

    /**
     * Set error
     *
     * @param string $data
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setError($data);

    /**
     * Set is successful
     *
     * @param bool $data
     * @return \Oxl\Delivery\Api\Data\OrderInterface
     */
    public function setIsSuccessful($data);

}
