<?php

namespace Oxl\Delivery\Api;

use Oxl\Delivery\Api\Data\OxlDeliveryInterface;

interface OxlDeliveryManagementInterface
{
    /**
     * get test Api data.
     *
     * @param int $id
     *
     * @return OxlDeliveryInterface
     * @api
     *
     */
    public function getApiData(int $id);

    /**
     * get test Api data.
     *
     * @return OxlDeliveryInterface
     * @api
     *
     */
    public function getIframeData();

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
     * @param ?string $office_name
     * @param ?string $office_name_only
     * @param ?string $other
     * @param ?string $phone
     * @param ?string $post_code
     * @param ?string $quarter
     * @param ?string $shipping_price_currency
     * @param ?string $shipping_price_currency_sign
     * @param ?string $street
     * @param ?string $zip
     * @return OxlDeliveryInterface
     * @api
     */
    public function setPaymentData(string  $econt_id, float $shipping_price, float $shipping_price_cod, ?string $address,
                                   ?string $city_name, ?string $country_code, ?string $country_name, ?string $email, ?string $face, ?int $id_country, ?string $name,
                                   ?string $num, ?string $office_code, ?string $office_name_only, ?string $office_name, ?string $other, ?string $phone,
                                   ?string $post_code, ?string $quarter, ?string $shipping_price_currency, ?string $shipping_price_currency_sign, ?string $street, ?string $zip);
}
