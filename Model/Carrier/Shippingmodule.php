<?php

namespace Oxl\Delivery\Model\Carrier;

use Magento\Customer\Model\Session;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

/**
 * Custom shipping model
 */
class Shippingmodule extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'econtdelivery';

    /**
     * @var bool
     */
    protected $_isFixed = false;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    private $rateMethodFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory
     */
    protected $rateErrorFactory;

    protected ?Session $_customerSession;

    protected \Magento\Checkout\Model\Session $_checkoutSession;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface          $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory,
        \Psr\Log\LoggerInterface                                    $logger,
        \Magento\Checkout\Model\Session                             $checkoutSession,
        \Magento\Shipping\Model\Rate\ResultFactory                  $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        Session                                                     $customerSession = null,
        ?array                                                      $data = null
    )
    {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data ?: []);

        $this->rateResultFactory = $rateResultFactory;
        $this->rateErrorFactory = $rateErrorFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Custom Shipping Rates Collector
     *
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        // NIMA CHANGES
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // important - do this only when there is a quote_id otherwise an infinite loop is triggered:
        // https://angrobg.sentry.io/issues/4150682499/?project=6597971&query=is%3Aunresolved&referrer=issue-stream&stream_index=2
        $quoteId = $this->_checkoutSession->getQuoteId();

        $econtData = (array)$this->_checkoutSession->getEcontData();

        if ($quoteId) {
            $payment_method = $this->_checkoutSession->getQuote()->getPayment()->getMethod();
            error_log('calculate ekont shipping (' . $payment_method . ')');

            // NIMA CHANGES
            if ($payment_method === 'cashondelivery') {
//                $shippingCost = $this->_checkoutSession->getEcontShippingPriceCod();
                $shippingCost = $econtData['shipping_price_cod'] ?? 0;
            } else {
//                $shippingCost = $this->_checkoutSession->getEcontShippingPrice();
                $shippingCost = $econtData['shipping_price'] ?? 0;
            }
        } else {
//            $shippingCost = $this->_checkoutSession->getEcontShippingPrice();
            $shippingCost = $econtData['shipping_price'] ?? 0;
        }

        error_log('calculated shipping cost - ekont -------------------------------------------------------------------------------------------------------------------------------: ' .
            print_r($econtData, true) . "\n\n\n" .
            $shippingCost);

//        if ($this->_checkoutSession->getEcontShippingPriceCod()) {
//            $shippingCost = $this->_checkoutSession->getEcontShippingPriceCod();
//        } else if ($payment_method != null && $payment_method === 'cashondelivery') {
//            $shippingCost = $this->_checkoutSession->getEcontShippingPriceCod();
//        } else if ($payment_method != null) {
//            $shippingCost = $this->_checkoutSession->getEcontShippingPrice();
//        }

        $result = $this->rateResultFactory->create();

        $success = true;

        if (!$success) {
            $error = $this->rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage('Error');
            $error->setPrice(0);
            $error->setCost(0);
            $result->append($error);
        } else {
            $method = $this->rateMethodFactory->create();

            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod($this->_code);
            $method->setMethodTitle($this->getConfigData('name'));
//
//        // $shippingCost = (float)$this->getConfigData('shipping_cost');
//        $shippingCost = $price;

            $method->setPrice($shippingCost);
            $method->setCost($shippingCost);

            $result->append($method);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}
