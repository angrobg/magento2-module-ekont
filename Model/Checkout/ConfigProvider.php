<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

namespace Oxl\Delivery\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Oxl\Delivery\Helper\Data;
use Oxl\Delivery\Helper\Order;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Data
     */
    protected Data $dataHelper;

    /**
     * @var Session
     */
    protected Session $checkoutSession;

    /**
     * @var Order
     */
    protected Order $orderHelper;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param Data $dataHelper
     * @param Order $orderHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Data    $dataHelper,
        Order   $orderHelper
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->dataHelper = $dataHelper;
        $this->orderHelper = $orderHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $carrier_code = Data::CARRIER_CODE;

        $result = [];
        /** @noinspection PhpUndefinedMethodInspection */
        $econtData = $this->checkoutSession->getEcontData();

        $result[$carrier_code]['carrierCode'] = $carrier_code;
        $result[$carrier_code]['orderData'] = $econtData;
        $result[$carrier_code]['baseUrl'] = $this->dataHelper->getBaseUrl();

        return $result;
    }
}
