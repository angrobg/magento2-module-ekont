define(['jquery'], function ($) {
    'use strict';

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                var result = this._super();
                return result;
            }
        });
    }
});
