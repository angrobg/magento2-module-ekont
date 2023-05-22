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

        console.log('ekontdelivery iframe msg', message.data);

        var modal = $('#econt-iframe-modal')
        var proceed = false;
        var content;

        if (data.shipment_error) {
            content = data.shipment_error
        } else {
            proceed = true

            var isCod = econtModalHelper.isCODSelected()
            var total = isCod ? data['shipping_price_cod'] : data['shipping_price'];

            content = $.mage.__('Цена на доставката: ')
                + Number((total).toFixed(2)) + ' ' + data['shipping_price_currency_sign'] +
                (isCod ? ' ' + $.mage.__('(с наложен платеж)') : '');

            // if (econtModalHelper.isCODSelected()) {
            //     content = content + " ( + " + Number((econtModalHelper.shipping_price_cod).toFixed(2)) +
            //         ' ' + data['shipping_price_currency_sign']
            //         + $.mage.__('с наложен платеж') + ').';
            // }
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
