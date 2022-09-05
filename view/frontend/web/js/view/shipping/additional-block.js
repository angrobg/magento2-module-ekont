define([
    'uiComponent',
    'jquery',
    'Magento_Ui/js/modal/modal'
], function (Component, $, modal) {
    'use strict';

    window.addEventListener('message', function (message) {

        //console.log('ekont message received', message);

        /**
         * check if this "message" comes from econt delivery system
         */
        if (message.origin.indexOf("//delivery") < 0) {
            return;
        }

        var data = message.data
        var modal = $('#econt-iframe-modal')
        var proceed = false;
        var content;

        if (data.shipment_error) {
            content = data.shipment_error
        } else {
            econtModalHelper.updateShippingAddress(data);
            proceed = true
            content = $.mage.__('Цена на доставката: ')
                + data['shipping_price'] + ' ' + data['shipping_price_currency_sign'];

            if (econtModalHelper.isCODSelected()) {
                content = content + " ( + " + econtModalHelper.shipping_price_cod + ' ' + data['shipping_price_currency_sign']
                    + $.mage.__(' наложен платеж') + ').';
            }
        }

        // NIMA CHANGES
        //modal.modal('toggleModal')

        econtModalHelper.showAlert(content, data, proceed, modal)
    });

    return Component.extend({
        defaults: {
            template: 'Oxl_Delivery/shipping/additional-block'
        }
    });
});
