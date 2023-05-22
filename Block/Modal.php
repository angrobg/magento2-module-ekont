<?php
/**
 * Copyright © 2016 AionNext Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Oxl\Delivery\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Aion Test Page block
 */
class Modal extends Template
{
    protected Session $checkoutSession;

    /**
     * Test constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        array   $data = []
    )
    {
        parent::__construct($context, $data);

        $this->checkoutSession = $checkoutSession;
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
