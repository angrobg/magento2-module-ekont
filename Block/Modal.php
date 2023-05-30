<?php
/**
 * Copyright © 2016 AionNext Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Oxl\Delivery\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Oxl\Delivery\Helper\Order;

/**
 * Aion Test Page block
 */
class Modal extends Template
{
    protected Session $checkoutSession;

    protected Order $orderHelper;

    /**
     * Test constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param Order $orderHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Order   $orderHelper,
        array   $data = []
    )
    {
        parent::__construct($context, $data);

        $this->checkoutSession = $checkoutSession;
        $this->orderHelper = $orderHelper;
    }

    public function getAjaxUrl(): string
    {
        return $this->getBaseUrl();
    }

    public function getEcontData()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->checkoutSession->getEcontData();
    }
}
