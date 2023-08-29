<?php

namespace Oxl\Delivery\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Url;
use Oxl\Delivery\Model\OxlDelivery;

class Data extends AbstractHelper
{
    const CARRIER_CODE = 'econtdelivery';

    /**
     * @var Url
     */
    protected Url $urlHelper;

    public function __construct(
        Context $context,
        Url     $urlHelper
    )
    {
        parent::__construct($context);

        $this->urlHelper = $urlHelper;
    }

    /**
     * Get the module config data
     *
     * @return string
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if we using Demo service
     *
     * @return bool
     */
    public function isDemo()
    {
        $options = $this->getConfig('carriers/econtdelivery/demo_service');

        return boolval($options);
    }

    public function isActive()
    {
        $options = $this->getConfig('carriers/econtdelivery/active');
        return boolval($options);
    }

    /**
     * Based on the demo setting returns the appropiate url
     *
     * @return string URL
     */
    public function getServiceUrl()
    {
        if ($this->isDemo()) {
            $url = OxlDelivery::DEMO_URL;
        } else {
            $url = OxlDelivery::REAL_URL;
        }
        // return ( is_ssl() ? 'https:' : 'http:' ) . $url;
        return $url;
    }

    /**
     * Retrieve the stored in database setting
     *
     * @param bool $encrypt Encrypt the string or not
     *
     * @return string
     */
    public function getPrivateKey($encrypt = false)
    {
        $key = $this->getConfig('carriers/econtdelivery/key');

        return $encrypt ? base64_encode($key) : $key;
    }

    /**
     * check stored configuration
     *
     * Check stored shop_id, private_key and demo_service options with Econt via curl request
     *
     * @param array $new_settings The settings entered by the user
     * @return array
     **/
    public function checkEcontConfiguration($new_settings = [], $order_number = '4812384')
    {
        $endpoint = $this->getServiceUrl(array_key_exists('demo_service', $new_settings));
        $secret = $new_settings['private_key'];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint . "services/OrdersService.getTrace.json");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: " . $secret,
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            'orderNumber' => $order_number,
        ]));
        curl_setopt($curl, CURLOPT_TIMEOUT, 6);
        $res = curl_exec($curl);
        $response = json_decode($res, true);

        curl_close($curl);

        // if( is_array( $response ) && array_key_exists('type', $response) && $response['type'] == 'ExAccessDenied' ) {
        //     return $response;

        // }

        return $response;
    }

    public function getWaybillPopupUrl($order_number)
    {
        $conf = ['private_key' => $this->getPrivateKey()];
        $data = $this->checkEcontConfiguration($conf, $order_number);
        return $data['pdfURL'];
    }

    /**
     * Shipping tracking popup URL getter
     *
     * @param \Magento\Sales\Model\AbstractModel $model
     * @return string
     */
    public function getTrackingPopupUrl($tracking_object)
    {
        $tracksCollection = $tracking_object->getTracksCollection();
        foreach ($tracksCollection->getItems() as $track) {
            $trackNumbers[] = $track->getTrackNumber();
        }
        $tracking_number = $trackNumbers[0];
        return 'https://www.econt.com/services/track-shipment/' . $tracking_number;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->urlHelper->getUrl('', ['_secure' => true]);
    }
}
