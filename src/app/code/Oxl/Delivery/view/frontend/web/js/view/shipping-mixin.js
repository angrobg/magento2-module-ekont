define(['jquery'], function ($) {
    'use strict';

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {

                // var result = this.validateExtensaSpeedyForm();
                //
                // if (result) {
                //     result = this._super();
                // }
                var result = false;

                return result;
            },
            // validateExtensaSpeedyForm: function () {
            //     var form = $('#extensa_speedy-form').closest('form');
            //
            //     if ($(form).validation() && $(form).validation('isValid')) {
            //         return true;
            //     }
            //
            //     return false;
            // }
        });
    }
});
