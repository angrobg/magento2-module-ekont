define(['jquery'], function ($) {
    'use strict';

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                return this._super();
            }
        });
    }
});
