<?php

namespace Oxl\Delivery\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Message\ManagerInterface;
use Oxl\Delivery\Api\Data\OrderInterfaceFactory;
use Oxl\Delivery\Api\OrderRepositoryInterface;
use Oxl\Delivery\Model\OxlDelivery;
use Oxl\Delivery\Model\OxlDeliveryFactory;
use Sentry\EventHint;
use Sentry\Severity;
use Sentry\State\Scope;
use function Sentry\captureMessage;
use function Sentry\configureScope;

class Order extends AbstractHelper
{
    protected Session $_checkoutSession;

    protected OxlDelivery $_oxlDeliveryFactory;

    protected ManagerInterface $_messageManager;

    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $ekontOrderRepository;

    /**
     * @var OrderInterfaceFactory
     */
    protected OrderInterfaceFactory $ekontOrderFactory;

    protected Data $dataHelper;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param OxlDeliveryFactory $oxldelivery
     * @param ManagerInterface $messageManager
     * @param OrderInterfaceFactory $ekontOrderFactory
     * @param OrderRepositoryInterface $ekontOrderRepository
     */
    public function __construct(
        Context                  $context,
        Session                  $checkoutSession,
        OxlDeliveryFactory       $oxldelivery,
        ManagerInterface         $messageManager,
        OrderInterfaceFactory    $ekontOrderFactory,
        OrderRepositoryInterface $ekontOrderRepository,
        Data                     $dataHelper
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_oxlDeliveryFactory = $oxldelivery->create();
        $this->_messageManager = $messageManager;
        $this->ekontOrderFactory = $ekontOrderFactory;
        $this->ekontOrderRepository = $ekontOrderRepository;
        $this->dataHelper = $dataHelper;

        parent::__construct($context);
    }

    public function syncOrder($order = null, $get_new_price = false)
    {
        if (!$this->dataHelper->isActive()) {
            return false;
        }

        error_log('Ekont syncOrder');

        if ($order === null) {
            error_log('Ekont sync_order: NO ORDER - DOING NOTHING');
            return false;
        }

        if (gettype($order) === 'integer') {
            $objectManager = ObjectManager::getInstance();
            /** @noinspection PhpDeprecationInspection */
            $order = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->load(intval($order));
        }

        error_log('Ekont sync_order: ' . $order->getIncrementId());

        if ($order->getShippingMethod() != 'econtdelivery_econtdelivery') {
            error_log('Ekont sync_order: NOT AN EKONT ORDER - DOING NOTHING');
            return false;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $ekontData = $this->_checkoutSession->getEcontData();

        $data = [
            'id' => '',
            'orderNumber' => $order->getId(),
            'status' => $order->getStatus(),
            'orderTime' => '',
            'cod' => $order->getPayment()->getMethod() === 'cashondelivery' ? true : '',
            'partialDelivery' => '',
            'currency' => $order->getOrderCurrencyCode(),
            'shipmentDescription' => '',
            'shipmentNumber' => '',
            'customerInfo' => [
                'id' => $ekontData['id'] ?? '',
                'name' => $ekontData['name'] ?? '',
                'face' => $ekontData['face'] ?? '',
                'phone' => $ekontData['phone'] ?? '',
                'email' => $ekontData['email'] ?? '',
                'countryCode' => $ekontData['country_code'] ?? '',
                'cityName' => $ekontData['city_name'] ?? '',
                'postCode' => $ekontData['post_code'] ?? '',
                'officeCode' => $ekontData['office_code'] ?? '',
                'zipCode' => $ekontData['zip'] ?? '',
                'address' => $ekontData['address'] ?? '',
                'priorityFrom' => '',
                'priorityTo' => '',
            ],
            'items' => [],
            "packCount" => null,
            "receiverShareAmount" => null,
        ];

        $iteration = count($order->getAllVisibleItems());

        foreach ($order->getAllVisibleItems() as $item) {
            $price = $item->getPrice();
            // $count  = $item->get_quantity();
            $weight = floatval($item->getWeight());
            $quantity = intval($item->getQtyOrdered());

            $data['items'][] = [
                'name' => $item->getName(),
                'SKU' => $item->getSku(),
                'URL' => '',
                'count' => $quantity,
                'hideCount' => '',
                'totalPrice' => $price * $quantity,
                'totalWeight' => $weight * $quantity,
            ];

            $data['shipmentDescription'] .= $item->getName() . ($iteration === 1 ? '' : ', ');
            $iteration -= 1;
        }

        $data['shipmentDescription'] = mb_strimwidth($data['shipmentDescription'], 0, 160, "...");

        if ($order->getTotalItemCount() > 1 && $data['cod']) {
            $data['partialDelivery'] = true;
        }

        error_log('Ekont sync_order: request: ' . json_encode($data));

        // log
        $ekontOrder = $this->ekontOrderFactory->create();
        $ekontOrder
            ->setDataColumn(json_encode(null))
            ->setRequest(json_encode($data))
            ->setDateCreated('now')
            ->setOrderId($order->getId());
        $this->ekontOrderRepository->save($ekontOrder);

        $curl = curl_init();

        $url = $this->_oxlDeliveryFactory->getEcontCustomerInfoUrl() .
            'services/OrdersService.updateOrder.json';

        // prod url for testing:
        // $url = 'https://delivery.econt.com/services/OrdersService.updateOrder.json';

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: ' . $this->_oxlDeliveryFactory->getPrivateKeyForOrder(),
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        $curl_error_no = curl_errno($curl);

        $parsed_error = json_decode($response, true);
        $is_successful = $http_status == 200 && !array_key_exists('type', $parsed_error);

        error_log('Ekont sync_order: response: ' . print_r($response, true) .
            "\n\nParsed: " . print_r($parsed_error, true));

        // store the resutls
        $ekontOrder
            ->setDataColumn(json_encode([
                'url' => $url,
                'response' => $response,
            ]))
            ->setError(json_encode([
                'parsedError' => $parsed_error,
                'httpStatus' => $http_status,
                'curlError' => $curl_error,
                'curlErrorNo' => $curl_error_no,
            ]))
            ->setIsSuccessful($is_successful);
        $this->ekontOrderRepository->save($ekontOrder);

        /** @noinspection PhpUndefinedMethodInspection */
        $this->_checkoutSession->unsEcontData();

        if (!$is_successful) {
            error_log('Ekont sync_order: response ERROR FOUND: ' . print_r($parsed_error, true));

//            $this->_messageManager->addErrorMessage($parsed_error['message']);

            configureScope(function (Scope $scope) use ($order, $data, $response, $parsed_error): void {
                $scope->setContext('Ekont Submission', [
                    'orderId' => $order->getIncrementId(),
                    'request' => $data,
                    'response' => $response,
                    'parsedError' => $parsed_error,
                ]);
            });

            captureMessage('Ekont order submission error (#' . $order->getIncrementId() . ')',
                Severity::warning(),
                EventHint::fromArray([
                    'extra' => [
                        'request' => $data,
                        'response' => $response,
                        'parsedError' => $parsed_error,
                    ],
                ]));

            return false;
        }

        return true;
    }
}
