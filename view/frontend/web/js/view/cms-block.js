define([
    'jquery',
    'underscore',
    'mage/storage',
    'mage/template',
    'Magento_Ui/js/modal/alert',
    'Amasty_CheckoutStyleSwitcher/js/model/amalert',
    'Magento_Checkout/js/checkout-data',
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
    amalert,
    checkoutData,
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
                econtData: {},
                paymentMethod: null,
                defaultPostCode: '1000',
                iframeLoadedOnce: false,
                carrier_code: 'econtdelivery',
                shippingPriceCalculated: false,
                shippingPriceNeedsRecalculation: true,
                cashondelivery_pm_code: 'cashondelivery',
                selected_carrier_code: '',
                calculationInProgress: false,
                lastSubtotal: quote.totals._latestValue.subtotal,
                lastEkontMessageData: null,
                initialize: function () {
                    this.baseUrl = config.ajaxUrl;
                    this.econtData = config.econtData;

                    console.log('econt init', {
                        baseUrl: this.baseUrl,
                        data: this.econtData
                    })

                    var self = this;

                    // NIMA CHANGES
                    quote.paymentMethod.subscribe(function (method) {
                        var shouldUpdatePaymentstate = this.paymentMethod !== null;
                        this.paymentMethod = null;

                        if (method) {
                            this.paymentMethod = method.method;
                        }

                        if (self.isCarrierSelected()) {
                            console.log('Payment method changed (ekont)', this.paymentMethod);

                            // self.shippingPriceNeedsRecalculation = true;

                            self.updateTotals(method);

                            // if (shouldUpdatePaymentstate) {
                            //     self.updatePaymentDataState(true);
                            // }
                        }

                    }, this);

                    // NIMA CHANGES
                    quote.shippingMethod.subscribe(function (val) {
                        if (self.selected_carrier_code !== val.carrier_code) {
                            self.selected_carrier_code = val.carrier_code;

                            if (self.isCarrierSelected()) {
                                console.log('ekontdelivery :: selected', val);
                                // self.updateShippingAddress();
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

                    // NIMA CHANGES
                    // reload the iframe when the customer data changes in case it was never calculated previously
                    // to autofill the customer data in the ekont form
                    quote.shippingAddress.subscribe(function (val) {
                        if (self.isCarrierSelected() && self.iframeLoadedOnce && !self.shippingPriceCalculated) {
                            console.log('ekontdelivery shippingAddressChangedHandler', val);

                            self.loadModal();
                            self.toggleCalculateshippingButton();
                        }
                    })

                    // NIMA CHANGES
                    quote.totals.subscribe(function (val) {
                        if (self.isCarrierSelected() && self.iframeLoadedOnce && self.lastSubtotal !== val['subtotal']) {
                            console.log('ekontdelivery totalsChangedHandler (shippingPriceNeedsRecalculation set)', val);

                            // self.shippingPriceCalculated = false;
                            // self.shippingPriceNeedsRecalculation = true;
                            self.lastSubtotal = val['subtotal'];

                            self.loadModal();
                            self.toggleCalculateshippingButton();

                            $(document).trigger('oxl-econt-shipping-cost-selected', {
                                isValid: false,
                                needsRecalculation: true
                            });
                        }
                    })

                    // NIMA CHANGES
                    // update the shipping address before changing it to the current one
                    $(document).on('checkout-before-select-shipping-method', function (e, data) {
                        if (data.method.carrier_code === self.carrier_code) {
                            self.updateShippingAddress();
                        }
                    });
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
                needsPriceRecalculation: function () {
                    return this.shippingPriceNeedsRecalculation;
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
                    // var data;
                    // var footer;
                    let cdata
                    cdata = customerData.get('checkout-data')();

                    // if (!this.checkCustomerData(cdata)) {
                    //     this.showAlert($.mage.__('Моля попълнете всички задължителни полета!'));
                    //     return
                    // }

                    this.prepareIframe(this.baseUrl, cdata);
                },
                prepareIframe: function (url, cdata) {
                    var iframe;
                    var orderParams = {};
                    var items = quote.getItems();

                    console.log('ekontdelivery ch', checkoutConfig);
                    console.log('ekontdelivery quote', quote);
                    console.log('ekontdelivery items', items);

                    orderParams.order_total = quote.totals._latestValue.subtotal //checkoutConfig.totalsData.subtotal_with_discount
                    orderParams.order_currency = checkoutConfig.totalsData.quote_currency_code
                    orderParams.order_weight = 0
                    orderParams.ignore_history = 1
                    orderParams.confirm_txt = 'Изчисли цената'

                    _.forEach(items, function (item) {
                        console.log('ekontdelivery item', item);

                        // find the totals item to obtain the quantity as the one from quote always stays with the initial value (even after being updated on the UI - may be a bug!)
                        var foundItem = null;

                        _.forEach(quote.totals._latestValue.items || [], function (xitem) {
                            console.log('ekontdelivery xitem', xitem);
                            if (xitem.item_id === parseInt(item.item_id)) {
                                foundItem = xitem;
                                return false;
                            }
                        });

                        if (foundItem) {
                            console.log('ekontdelivery foundItem', foundItem);
                            orderParams.order_weight += (parseFloat(item.weight) * foundItem.qty);
                        }
                    })

                    console.log('ekontdelivery lastOrderParams', this.lastOrderParams);

                    // reuse the previously saved values as they tend to get lost
                    if (this.econtData !== null) {
                        orderParams.customer_name = this.econtData.name || ''
                        orderParams.customer_company = this.econtData.face || ''
                        orderParams.customer_phone = this.econtData.phone || ''
                        orderParams.customer_email = this.econtData.email || ''
                        orderParams.customer_post_code = this.econtData.post_code || ''
                        orderParams.customer_city_name = this.econtData.city_name || ''
                        orderParams.customer_address = this.econtData.address || ''
                        orderParams.customer_office_code = this.econtData.office_code || ''
                    } else if (this.lastEkontMessageData !== null) {
                        orderParams.customer_name = this.lastEkontMessageData.name || ''
                        orderParams.customer_company = this.lastEkontMessageData.face || ''
                        orderParams.customer_phone = this.lastEkontMessageData.phone || ''
                        orderParams.customer_email = this.lastEkontMessageData.email || ''
                        orderParams.customer_post_code = this.lastEkontMessageData.post_code || ''
                        orderParams.customer_city_name = this.lastEkontMessageData.city_name || ''
                        orderParams.customer_address = this.lastEkontMessageData.address || ''
                        orderParams.customer_office_code = this.lastEkontMessageData.office_code || ''
                    } else {
                        orderParams.customer_name = ''
                        orderParams.customer_company = ''
                        orderParams.customer_phone = ''
                        orderParams.customer_email = ''
                        orderParams.customer_post_code = ''
                        orderParams.customer_city_name = ''
                        orderParams.customer_address = ''
                        orderParams.customer_office_code = ''
                    }

                    orderParams.customer_name = orderParams.customer_name.length ?
                        orderParams.customer_name.length :
                        ((cdata.shippingAddressFromData.firstname || '') + ' ' +
                            (cdata.shippingAddressFromData.lastname || '')).trim()

                    orderParams.customer_company = orderParams.customer_company.length ?
                        orderParams.customer_company :
                        (cdata.shippingAddressFromData.company || '')

                    orderParams.customer_phone = orderParams.customer_phone.length ?
                        orderParams.customer_phone :
                        (cdata.shippingAddressFromData.telephone || '')

                    orderParams.customer_email = orderParams.customer_email.length ?
                        orderParams.customer_email :
                        (cdata.validatedEmailValue || '')

                    // pass the following info only if it is full - otherwise Ekont shows a stupid mistake
                    if ((cdata.shippingAddressFromData.city || '').length &&
                        (orderParams.customer_post_code === '-' ||
                        !orderParams.customer_post_code ? this.defaultPostCode : orderParams.customer_post_code).length &&
                        cdata.shippingAddressFromData.street.length
                    ) {
                        orderParams.customer_city_name = orderParams.customer_city_name.length ?
                            orderParams.customer_city_name :
                            (cdata.shippingAddressFromData.city || '')

                        if (!orderParams.customer_post_code.length) {
                            orderParams.customer_post_code = cdata.shippingAddressFromData.postcode || ''
                            orderParams.customer_post_code = (orderParams.customer_post_code === '-' ||
                            !orderParams.customer_post_code ? this.defaultPostCode : orderParams.customer_post_code);
                        }

                        if (!orderParams.customer_address.length) {
                            orderParams.customer_address = ''
                            _.forEach(cdata.shippingAddressFromData.street, function (str, index) {
                                if (index > 0 && str.length > 0 && index <= (_.size(cdata.shippingAddressFromData.street) - 1)) {
                                    orderParams.customer_address += ', ';
                                }
                                orderParams.customer_address += str;
                            })
                        }
                    }

                    var iframeContainer = $('#place_iframe_here');
                    var self = this;

                    this.lastOrderParams = orderParams;

                    console.log('Ekont prepare iframe', {
                        items: items,
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
                storeSessionPriceData: function (data, callbackSuccess, callbackFail) {
                    if (this.calculationInProgress) {
                        return;
                    }

                    var me = this
                    this.calculationInProgress = true

                    storage.post(
                        this.baseUrl + 'rest/V1/econt/delivery/set-payment-data',
                        JSON.stringify({
                            econt_id: data.id,
                            shipping_price: data.shipping_price,
                            shipping_price_cod: data.shipping_price_cod,
                            // NIMA CHANGES START - pass additional fields
                            address: data.address || '',
                            city_name: data.city_name || '',
                            country_code: data.country_code || '',
                            country_name: data.country_name || '',
                            email: data.email || '',
                            face: data.face || '',
                            id_country: data.id_country || null,
                            name: data.name || '',
                            num: data.num || '',
                            office_code: data.office_code || '',
                            office_name: data.office_name || '',
                            office_name_only: data.office_name_only || '',
                            other: data.other || '',
                            phone: data.phone || '',
                            post_code: data.post_code || '',
                            quarter: data.quarter || '',
                            shipping_price_currency: data.shipping_price_currency || '',
                            shipping_price_currency_sign: data.shipping_price_currency_sign || '',
                            street: data.street || '',
                            zip: data.zip || ''
                            // NIMA CHANGES END
                        }),
                        false
                    ).done(function (result) {
                        console.log('storeSessionPriceData completed', result);

                        me.calculationInProgress = false

                        if (callbackSuccess !== undefined) {
                            callbackSuccess(result);
                        }
                    }).fail(function (response) {
                        console.log('storeSessionPriceData error', response);

                        me.calculationInProgress = false

                        if (callbackFail !== undefined) {
                            callbackFail(response);
                        }
                    })
                },
                updateShippingAddress: function () {

                    console.log('ekontdelivery :: updateShippingAddress');

                    var data = this.shipping_data;

                    data['face'] = data['face'] || '';
                    data['name'] = data['name'] || '';
                    data['address'] = data['address'] || '';
                    data['office_name'] = data['office_name'] || '';
                    data['phone'] = data['phone'] || '';
                    data['post_code'] = data['post_code'] || '';
                    data['city_name'] = data['city_name'] || '';
                    data['email'] = data['email'] || '';

                    var full_name = [];
                    var company = '';
                    var updateBilling;

                    // updateBilling = quote.billingAddress() !== null;
                    updateBilling = false;

                    if (data['face']) {
                        full_name = data['face'].split(' ');
                        company = data['name'];
                    } else {
                        full_name = data['name'] || ''.split(' ');
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

                    var addrLine = data['address'] !== '' ? data['address'] : data['office_name'];

                    quote.shippingAddress().street.splice(0, quote.shippingAddress().street.length);
                    quote.shippingAddress().street.push(addrLine);

                    // quote.shippingAddress().street[0] = data['address'] !== '' ? data['address'] : data['office_name'];

                    if (updateBilling) {
                        quote.billingAddress().street.splice(0, quote.billingAddress().street.length);
                        quote.billingAddress().street.push(addrLine);
                        // quote.billingAddress().street[0] = data['address'] !== '' ? data['address'] : data['office_name'];
                    }

                    console.log('ekont :: updateShippingAddress address line', {
                        addrLine: addrLine,
                        shippingStreet: quote.shippingAddress().street,
                        billingStreet: quote.billingAddress().street
                    });

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

                    console.log('ekont :: updateShippingAddress', {
                        data: data,
                        quote: quote
                    });

                    console.log('ekont :: updateShippingAddress', quote.shippingAddress());

                    checkoutData.setNewCustomerShippingAddress(quote.shippingAddress());
                    // checkoutData.setNewCustomerBillingAddress(quote.billingAddress());
                },
                updatePaymentDataState: function (silent) {
                    // console.log('updatePaymentDataState');

                    console.log('ekont :: updatePaymentDataState');

                    silent = silent || false;
                    var shipping_data = this.shipping_data;

                    // if (Object.keys(shipping_data).length === 0) {
                    //     return;
                    // }
                    //

                    console.log('updatePaymentDataState data', {
                        shipping_data: shipping_data,
                        isCod: this.isCODSelected()
                    })

                    // var priceDataField = this.isCODSelected() ? 'shipping_price_cod' : 'shipping_price';
                    // var priceData = shipping_data[priceDataField] || 0;

                    var priceData = this.isCODSelected() ? this.shipping_price_cod :
                        this.shipping_price;

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
                        // _that.updateQuoteShippingTotals(priceData);
                        _that.updateTotals(priceData);
                    }).fail(function (response) {
                        shippingService.setShippingRates([]);
                        errorProcessor.process(response);
                    }).always(function () {
                        if (!silent) {
                            shippingService.isLoading(false);
                        }
                    });
                },
                updateTotals: function (priceData) {
                    // var totals = quote.getTotals()();
                    var shippingAmount = this.isCODSelected() ? this.shipping_price_cod :
                        this.shipping_price;

                    if (!shippingAmount) {
                        console.log('updateTotals EKONT - no price, skipping!');
                        return;
                    }

                    if (priceData !== undefined) {
                        shippingAmount = priceData;
                    }

                    console.log('updateTotals EKONT', shippingAmount);

                    // if (this.shipping_price_cod === null) {
                    //     setTimeout(function () {
                    //         stepNavigator.navigateTo('shipping', 'opc-shipping_method');
                    //         sidebarModel.hide();
                    //     }, 1000)
                    // }

                    this.updateQuoteShippingTotals(shippingAmount, false, false);

                    // if (this.isCODSelected()) {
                    //     console.log('AAAAA');
                    //     if (totals.base_shipping_incl_tax < this.shipping_data.shipping_price_cod) {
                    //         this.updateQuoteShippingTotals(this.shipping_price_cod, true);
                    //     }
                    // } else {
                    //     console.log('BBBBB');
                    //     if (totals.base_shipping_incl_tax > this.shipping_data.shipping_price) {
                    //         this.updateQuoteShippingTotals(this.shipping_price_cod, false, true);
                    //     }
                    // }
                },
                updateQuoteShippingTotals: function (data, add_cod = false, sub_cod = false) {
                    console.log('updateQuoteShippingTotals', {
                        data: data,
                        add_cod: add_cod,
                        sub_cod: sub_cod
                    })

                    var t = quote.getTotals()();

                    console.log('updateQuoteShippingTotals QUOTE BEFORE', t);

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
                                segment.value = t.grand_total /*+ data;*/;
                        }

                        return segment;
                    });

                    console.log('RECALC QUOTE SEGMENTS (EKONT)', t)

                    quote.setTotals(t);
                },
                showAlert: function (content, data = null, proceed = false, modal = null) {
                    var _that = this;
                    amalert({
                        title: $.mage.__('Доставка с Еконт'),
                        content: content,
                        actions: {
                            always: function () {
                                if (proceed && data) {
                                    _that.storeSessionPriceData(data, function () {
                                        _that.lastEkontMessageData = data;
                                        _that.shipping_data = data;
                                        _that.shipping_price_cod = data['shipping_price_cod'];
                                        // _that.shipping_price_cod = Math.round((data['shipping_price_cod'] - data['shipping_price']) * 100) / 100;
                                        _that.shipping_price = data['shipping_price'];

                                        // $('#place_iframe_here').empty();
                                        _that.shippingPriceCalculated = true;
                                        _that.shippingPriceNeedsRecalculation = false;

                                        $(document).trigger('oxl-econt-shipping-cost-selected', {
                                            isValid: _that.shippingPriceCalculated,
                                            needsRecalculation: _that.shippingPriceNeedsRecalculation
                                        });

                                        _that.updateShippingAddress();
                                        _that.updatePaymentDataState();
                                    });
                                }/* else {
                                    if (modal)
                                        modal.modal('toggleModal');
                                }*/
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
