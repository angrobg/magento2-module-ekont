define([
    'jquery',
    'underscore',
    'mage/storage',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/sidebar',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/action/set-shipping-information'
], function (
    $,
    _,
    storage,
    template,
    alert,
    modal,
    quote,
    customerData,
    sidebarModel,
    stepNavigator,
    errorProcessor,
    shippingService,
    resourceUrlManager,
    rateRegistry,
    setShippingInformationAction,
) {
    'use strict'

    // NIMA CHANGES - we don't want to use a modal here
    var useModal = false;

    // NIMA CHANGES
    // if (quote.shippingMethod() && quote.shippingMethod().carrier_code !== "econtdelivery") {
    //     return;
    // }

    // var helperObj = new econtModalHelper();
    // window.econtModalHelper = helperObj;

    return function (config, element) {
        var helperObj = new function () {
            var obj = {
                shipping_data: {},
                shipping_price_cod: null,
                baseUrl: '',
                paymentMethod: null,
                defaultPostCode: '1000',
                iframeLoadedOnce: false,
                carrier_code: 'econtdelivery',
                shippingPriceCalculated: false,
                cashondelivery_pm_code: 'cashondelivery',
                selected_carrier_code: '',
                initialize: function () {
                    this.baseUrl = config.AjaxUrl;

                    var self = this;

                    // NIMA CHANGES
                    quote.paymentMethod.subscribe(function (method) {
                        var shouldUpdatePaymentstate = this.paymentMethod !== null;
                        this.paymentMethod = null;

                        if (method) {
                            this.paymentMethod = method.method;
                        }
                        console.log('Payment method changed (ekont)', this.paymentMethod);

                        self.updateTotals(method);

                        // if (shouldUpdatePaymentstate) {
                        //     self.updatePaymentDataState(true);
                        // }
                    }, this);

                    // NIMA CHANGES
                    quote.shippingMethod.subscribe(function (val) {
                        if (self.selected_carrier_code !== val.carrier_code) {
                            self.selected_carrier_code = val.carrier_code;

                            if (self.isCarrierSelected()) {
                                console.log('ekontdelivery selected');
                                self.loadIFrameOnce();
                            }
                            self.toggleCalculateshippingButton();
                        }
                        // econtModalHelper.toggleCalculateshippingButton(val);
                        // if( ! stepNavigator.isProcessed( 'shipping' ) && val['carrier_code'] === "econtdelivery" && shipping_price_cod === null ) {
                        //     stepNavigator.navigateTo('shipping', 'opc-shipping_method');
                        //     sidebarModel.hide();
                        // }
                    })
                },
                isCODSelected: function () {
                    return this.paymentMethod === this.cashondelivery_pm_code;
                },
                isCarrierSelected: function () {
                    return this.selected_carrier_code === this.carrier_code;
                },
                loadIFrameOnce: function () {
                    if (this.iframeLoadedOnce) {
                        return;
                    }

                    this.iframeLoadedOnce = true;
                    var self = this;

                    setTimeout(function () {
                        self.loadModal();
                    }, 800)
                },
                isShippingPriceCalculated: function () {
                    return this.shippingPriceCalculated;
                },
                toggleCalculateshippingButton: function () {
                    var self = this;

                    setTimeout(function () {
                        $('#econt-iframe-modal').toggle(self.isCarrierSelected());
                        // $('#block-econtdelivery-custom').toggle(self.isCarrierSelected());
                        // if (object.carrier_code !== 'econtdelivery') {
                        //     $('#block-econtdelivery-custom').hide()
                        // } else if (object.carrier_code === 'econtdelivery') {
                        //     $('#block-econtdelivery-custom').show()
                        // }
                    }, 800)
                },
                loadModal: function () {
                    if (!this.isCarrierSelected()) {
                        return;
                    }

                    // if (quote.shippingMethod().carrier_code !== 'econtdelivery') return;
                    var data;
                    var footer;
                    let cdata
                    cdata = customerData.get('checkout-data')();

                    if (!this.checkCustomerData(cdata)) {
                        this.showAlert($.mage.__('???????? ?????????????????? ???????????? ???????????????????????? ????????????!'));
                        return
                    }

                    if (useModal) {
                        var iframeModalContainer = $('#econt-iframe-modal');

                        data = {
                            'type': "popup",
                            'title': $.mage.__('???????????????? ?? ??????????'),
                            'responsive': true,
                            'showLoader': true,
                            // 'buttons': [{
                            //     text: jQuery.mage.__('Submit'),
                            //     class: 'action'
                            // }],
                            opened: false
                        }

                        // NIMA CHANGES
                        this.prepareIframe(this.baseUrl, cdata);

                        iframeModalContainer.modal(data);
                        iframeModalContainer.modal('openModal');
                        footer = $('.modal-footer')
                        footer.css('display', 'none');
                    } else {
                        this.prepareIframe(this.baseUrl, cdata);
                    }
                },
                prepareIframe: function (url, cdata) {
                    var iframe;
                    var orderParams = {};
                    var items = quote.getItems();

                    orderParams.order_total = checkoutConfig.totalsData.subtotal_with_discount
                    orderParams.order_currency = checkoutConfig.totalsData.quote_currency_code
                    orderParams.customer_name = ((cdata.shippingAddressFromData.firstname || '') + ' ' +
                        (cdata.shippingAddressFromData.lastname || '')).trim()
                    orderParams.customer_company = cdata.shippingAddressFromData.company || ''
                    orderParams.customer_address = ''
                    orderParams.order_weight = 0
                    orderParams.customer_city_name = cdata.shippingAddressFromData.city || ''

                    orderParams.customer_post_code = cdata.shippingAddressFromData.postcode || ''
                    orderParams.customer_post_code = (orderParams.customer_post_code === '-' ||
                    !orderParams.customer_post_code ? this.defaultPostCode : orderParams.customer_post_code);

                    orderParams.customer_phone = cdata.shippingAddressFromData.telephone || ''
                    orderParams.ignore_history = 1
                    orderParams.customer_email = cdata.validatedEmailValue || ''

                    _.forEach(items, function (item) {
                        orderParams.order_weight += item.weight
                    })

                    _.forEach(cdata.shippingAddressFromData.street, function (str, index) {
                        if (index > 0 && str.length > 0 && index <= (_.size(cdata.shippingAddressFromData.street) - 1)) {
                            orderParams.customer_address += ', ';
                        }
                        orderParams.customer_address += str;
                    })

                    _.forEach(items, (item, index) => {
                        orderParams.order_weight += Number(item.weight)
                    })

                    var iframeContainer = $('#place_iframe_here');
                    var self = this;

                    console.log('Ekont prepare iframe', {
                        cdata: cdata,
                        orderParams: orderParams
                    });

                    $.ajax({
                        // showLoader: true,
                        url: url + 'rest/V1/econt/delivery/get-iframe-data',
                        // data: param,
                        type: "GET",
                        dataType: 'json'
                    }).done(function (data) {
                        iframeContainer.empty();
                        orderParams.id_shop = data.econt_shop_id
                        // NIMA CHANGES
                        // iframe = '<iframe src="' + data.econt_customer_info_url + jQuery.param(orderParams) +
                        //     '" scrolling="yes" id="delivery_with_econt_iframe"></iframe>'
                        iframe = '<iframe scrolling="no" src="' + data.econt_customer_info_url + jQuery.param(orderParams) +
                            '" id="delivery_with_econt_iframe"></iframe>'
                        // append the generated iframe in the div
                        iframeContainer.append(iframe);
                    }).error(function (err) {
                        console.error('Could not load ekont iframe', err);
                        self.iframeLoadedOnce = false;
                    });
                },
                storeSessionPriceData: function (data) {
                    storage.post(
                        this.baseUrl + 'rest/V1/econt/delivery/set-payment-data',
                        JSON.stringify({
                            econt_id: data.id,
                            shipping_price: data.shipping_price,
                            shipping_price_cod: data.shipping_price_cod
                        }),
                        false
                    ).done(function (result) {
                        console.log('storeSessionPriceData completed', result);
                    }).fail(function (response) {
                        console.log('storeSessionPriceData error', response);
                    })
                },
                updateShippingAddress: function (data) {
                    console.log('updateShippingAddress', data);

                    this.shipping_price_cod = Math.round((data['shipping_price_cod'] - data['shipping_price']) * 100) / 100;
                    this.shipping_data = data

                    var full_name = [];
                    var company = '';
                    var updateBilling;

                    updateBilling = quote.billingAddress() !== null;

                    if (data['face'] != null) {
                        full_name = data['face'].split(' ');
                        company = data['name'];
                    } else {
                        full_name = data['name'].split(' ');
                    }

                    if (quote.shippingAddress().firstname !== full_name[0]) {
                        quote.shippingAddress().firstname = full_name[0];
                        if (updateBilling)
                            quote.billingAddress().firstname = full_name[0];
                    }

                    if (quote.shippingAddress().lastname !== full_name[1]) {
                        quote.shippingAddress().lastname = full_name[1];
                        if (updateBilling)
                            quote.billingAddress().lastname = full_name[1];
                    }

                    if (quote.shippingAddress().company !== company) {
                        quote.shippingAddress().company = company;
                        if (updateBilling)
                            quote.billingAddress().company = company;
                    }

                    quote.shippingAddress().street[0] = data['address'] !== '' ? data['address'] : data['office_name'];
                    if (updateBilling)
                        quote.billingAddress().street[0] = data['address'] !== '' ? data['address'] : data['office_name'];

                    if (quote.shippingAddress().telephone !== data['phone']) {
                        quote.shippingAddress().telephone = data['phone'];
                        if (updateBilling)
                            quote.billingAddress().telephone = data['phone'];
                    }

                    if (quote.shippingAddress().postcode !== data['post_code']) {
                        quote.shippingAddress().postcode = data['post_code'];
                        if (updateBilling)
                            quote.billingAddress().postcode = data['post_code'];
                    }

                    if (quote.shippingAddress().city !== data['city_name']) {
                        quote.shippingAddress().city = data['city_name'];
                        if (updateBilling)
                            quote.billingAddress().city = data['city_name'];
                    }

                    if (quote.guestEmail !== data['email']) {
                        quote.guestEmail = data['email'];
                    }
                },
                updateShippingPrice: function (data) {
                    this.shipping_data = data;
                    this.updatePaymentDataState();
                },
                updatePaymentDataState: function (silent) {
                    // console.log('updatePaymentDataState');

                    console.log('ekont :: requestShippingEstimate / updatePaymentDataState');

                    var self = this;
                    silent = silent || false;
                    var shipping_data = this.shipping_data;

                    if (Object.keys(shipping_data).length === 0) {
                        return;
                    }

                    console.log('updatePaymentDataState data', {
                        shipping_data: shipping_data,
                        isCod: this.isCODSelected()
                    })

                    var priceDataField = this.isCODSelected() ? 'shipping_price_cod' : 'shipping_price';
                    var priceData = shipping_data[priceDataField] || 0;

                    console.log('updatePaymentDataState price', priceData);

                    var address = quote.shippingAddress();
                    var _that = this;

                    if (!silent) {
                        shippingService.isLoading(true);
                    }
                    storage.post(
                        resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote),
                        JSON.stringify({
                            address: address
                        }),
                        false
                    ).done(function (result) {
                        var r = _.each(result, function (res) {
                            if (res.carrier_code === 'econtdelivery') {
                                res.amount = priceData;
                                res.base_amount = priceData;
                                res.price_excl_tax = priceData;
                                res.price_incl_tax = priceData;
                            }

                            return res;
                        });
                        rateRegistry.set(address.getKey(), r);
                        shippingService.setShippingRates(r);
                        _that.updateQuoteShippingTotals(priceData);
                    }).fail(function (response) {
                        shippingService.setShippingRates([]);
                        errorProcessor.process(response);
                    }).always(function () {
                        if (!silent) {
                            shippingService.isLoading(false);
                        }
                    });
                },
                updateTotals: function (data) {
                    var totals = quote.getTotals()();
                    if (Object.keys(this.shipping_data).length === 0) return;
                    if (quote.shippingMethod() && quote.shippingMethod().carrier_code !== "econtdelivery") return;

                    if (this.shipping_price_cod === null) {
                        setTimeout(function () {
                            stepNavigator.navigateTo('shipping', 'opc-shipping_method');
                            sidebarModel.hide();
                        }, 1000)
                    }

                    if (data.method === this.cashondelivery_pm_code) {
                        if (totals.base_shipping_incl_tax < this.shipping_data.shipping_price_cod)
                            this.updateQuoteShippingTotals(this.shipping_price_cod, true);
                    } else {
                        if (totals.base_shipping_incl_tax > this.shipping_data.shipping_price) {
                            this.updateQuoteShippingTotals(this.shipping_price_cod, false, true);
                        }
                    }
                },
                updateQuoteShippingTotals: function (data, add_cod = false, sub_cod = false) {
                    var t = quote.getTotals()();
                    var shipping_fields = [
                        'base_shipping_amount',
                        'base_shipping_incl_tax',
                        'shipping_amount',
                        'shipping_incl_tax',
                    ];
                    var subtotal_fields = [
                        'subtotal_with_discount'
                    ];

                    _.each(shipping_fields, function (field) {
                        if (add_cod) {
                            t[field] += data;
                        } else if (sub_cod) {
                            t[field] -= data;
                        } else {
                            t[field] = data;
                        }
                    });

                    _.each(subtotal_fields, function (field) {
                        if (add_cod)
                            t[field] += data;
                        else if (sub_cod)
                            t[field] -= data;
                        else
                            t[field] = t.subtotal + data;
                    });

                    _.each(t.total_segments, function (segment) {
                        if (segment.code === 'shipping') {
                            if (add_cod)
                                segment.value += data;
                            else if (sub_cod)
                                segment.value -= data;
                            else
                                segment.value = data;

                            if (segment.title.indexOf('(Deliver With Econt - Econt Shipping)') === -1) {
                                segment.title += ' (Deliver With Econt - Econt Shipping)'
                            }
                            // } else if ( segment.code === 'subtotal' ) {
                            //     if ( add_cod )
                            //         segment.value += data;
                            //     else if ( sub_cod )
                            //         segment.value -= data;
                            //     else
                            //         segment.value = t.subtotal + data;
                        } else if (segment.code === 'grand_total') {
                            if (add_cod)
                                segment.value += data;
                            else if (sub_cod)
                                segment.value -= data;
                            else
                                segment.value = t.grand_total + data;
                        }

                        return segment;
                    });

                    quote.setTotals(t);
                },
                showAlert: function (content, data = null, proceed = false, modal = null) {
                    var _that = this;
                    alert({
                        title: $.mage.__('???????????????? ?? ??????????'),
                        content: content,
                        actions: {
                            always: function () {
                                if (proceed && data) {
                                    _that.updateShippingPrice(data);
                                    // $('#place_iframe_here').empty();
                                    _that.storeSessionPriceData(data);
                                    _that.shippingPriceCalculated = true;

                                    $(document).trigger('oxl-econt-shipping-cost-selected', {
                                        isValid: _that.shippingPriceCalculated
                                    });

                                } else {
                                    if (modal)
                                        modal.modal('toggleModal');
                                }
                            }
                        }
                    });
                },
                checkCustomerData: function (data) {
                    var succss = false;
                    _.forEach(data.shippingAddressFromData, function (value, key) {
                        if (key !== 'region' && value !== "") {
                            succss = true;
                        } else if (key !== 'company' && value !== "") {
                            succss = true;
                        } else succss = key === 'region' || key === 'company';
                    })

                    return succss;
                }
            };
            obj.initialize();
            return obj;
        };
        window.econtModalHelper = helperObj;
        return helperObj;
    }
});
