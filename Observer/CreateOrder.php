<?php

namespace Oxl\Delivery\Observer;

use \Oxl\Delivery\Helper\Order;
use function Sentry\captureException;

class CreateOrder implements \Magento\Framework\Event\ObserverInterface
{
    protected $helper;

    public function __construct(Order $helper)
    {
        $this->helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        try {
            $this->helper->syncOrder($order);
        } catch (\Exception $e) {
            error_log('Ekont Exception: ' . $e->getMessage() . ', trace: ' . $e->getTraceAsString() . ', line: ' . $e->getLine());

            // log the exception and continue - we should not throw here for better UX
            captureException($e);
        }
    }
}
