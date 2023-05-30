<?php

namespace Oxl\Delivery\Model\Api;

use Oxl\Delivery\Api\OxlDeliveryManagementInterface;
use Oxl\Delivery\Helper\DataFactory;
use Oxl\Delivery\Helper\Order;
use Oxl\Delivery\Model\OxlDelivery;
use Oxl\Delivery\Model\OxlDeliveryFactory;

class OxlDeliveryManagement implements OxlDeliveryManagementInterface
{
    const SEVERE_ERROR = 0;
    const SUCCESS = 1;
    const LOCAL_ERROR = 2;

    protected $_testApiFactory;
    protected $helper;
    protected $order;

    public function __construct(
        OxlDeliveryFactory $testApiFactory,
        DataFactory        $data,
        Order              $order
    )
    {
        // var_dump($data);die();
        $this->_testApiFactory = $testApiFactory;
        $this->helper = $data;
        $this->order = $order;
    }

    /**
     * get test Api data.
     *
     * @param int $id
     *
     * @return \Oxl\Delivery\Api\Data\OxlDeliveryInterface
     * @api
     *
     */
    public function getApiData(int $id)
    {
        try {
            $this->order->syncOrder($id);

            // return $model;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $returnArray['error'] = $e->getMessage();
            $returnArray['status'] = 0;
            return $returnArray;
        } catch (\Exception $e) {
            dd($e);
            $returnArray['error'] = __('unable to process request');
            $returnArray['status'] = 2;
            return $returnArray;
        }
    }

    /**
     * get iframe url.
     *
     * @return \Oxl\Delivery\Api\Data\OxlDeliveryInterface
     * @api
     *
     */
    public function getIframeData()
    {
        $model = $this->_testApiFactory
            ->create();

//        $model->prepareModel(['shop_id', 'customer_info_url']);
        $model->prepareModel(['shop_id']);

        // NIMA CHANGES: we want to always show the REAL iframe to be able to debug
        // only the submission to EKONT upon order creation will check if it needs to submit to DEMO/REAL
        $model->setCustomerInfoUrl(OxlDelivery::REAL_URL . 'customer_info.php?');

        return $model;
    }


    /**
     * Set payment data
     *
     * @param string $econt_id
     * @param float $shipping_price
     * @param float $shipping_price_cod
     * @param ?string $address
     * @param ?string $city_name
     * @param ?string $country_code
     * @param ?string $country_name
     * @param ?string $email
     * @param ?string $face
     * @param ?int $id_country
     * @param ?string $name
     * @param ?string $num
     * @param ?string $office_code
     * @param ?string $office_name_only
     * @param ?string $office_name
     * @param ?string $other
     * @param ?string $phone
     * @param ?string $post_code
     * @param ?string $quarter
     * @param ?string $shipping_price_currency
     * @param ?string $shipping_price_currency_sign
     * @param ?string $street
     * @param ?string $zip
     * @return int
     * @return \Oxl\Delivery\Api\Data\OxlDeliveryInterface
     * @api
     */
    public function setPaymentData(string  $econt_id, float $shipping_price, float $shipping_price_cod, ?string $address,
                                   ?string $city_name, ?string $country_code, ?string $country_name, ?string $email, ?string $face, ?int $id_country, ?string $name,
                                   ?string $num, ?string $office_code, ?string $office_name_only, ?string $office_name, ?string $other, ?string $phone,
                                   ?string $post_code, ?string $quarter, ?string $shipping_price_currency, ?string $shipping_price_currency_sign, ?string $street, ?string $zip)
    {
        $model = $this->_testApiFactory->create();

        if (!$econt_id || !$shipping_price || !$shipping_price_cod) {
            return self::SEVERE_ERROR;
        }

//        $model->getCheckoutSession()->setEcontId($econt_id);
//        $model->getCheckoutSession()->setEcontShippingPrice($shipping_price);
//        $model->getCheckoutSession()->setEcontShippingPriceCod($shipping_price_cod);

        $model->getCheckoutSession()->setEcontData([
            'id' => $econt_id,
            'shipping_price' => $shipping_price,
            'shipping_price_cod' => $shipping_price_cod,
            'address' => $address,
            'city_name' => $city_name,
            'country_code' => $country_code,
            'country_name' => $country_name,
            'email' => $email,
            'face' => $face,
            'id_country' => $id_country,
            'name' => $name,
            'num' => $num,
            'office_code' => $office_code,
            'office_name_only' => $office_name_only,
            'office_name' => $office_name,
            'other' => $other,
            'phone' => $phone,
            'post_code' => $post_code,
            'quarter' => $quarter,
            'shipping_price_currency' => $shipping_price_currency,
            'shipping_price_currency_sign' => $shipping_price_currency_sign,
            'street' => $street,
            'zip' => $zip,
        ]);

        return self::SUCCESS;
    }
}
