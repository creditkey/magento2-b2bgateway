/*browser:true*/
/*global define*/
define(
    [
      'jquery',
      'Magento_Checkout/js/view/payment/default',
      'Magento_Checkout/js/action/place-order',
      'Magento_Checkout/js/action/select-payment-method',
      'Magento_Checkout/js/checkout-data',
      'Magento_Checkout/js/model/payment/additional-validators',
      'Magento_Checkout/js/action/set-payment-information',
      'Magento_Checkout/js/model/quote',
      'Magento_SalesRule/js/model/payment/discount-messages',
      'Magento_Customer/js/model/customer',
      'creditkeysdk',
      'CreditKey_B2BGateway/js/jquery.livequery.min'
    ],
    function ($, Component, placeOrderAction, selectPaymentMethodAction, checkoutData, additionalValidators, setPaymentInformation, quote, messageContainer, customerModel, creditKey) {
        'use strict';

        var originalOrderButton, originalOrderButtonVal;
        var data = window.checkoutConfig.payment.creditkey_gateway;
        var ckClient = new creditKey.Client(data.publicKey, data.endpoint);

        quote.paymentMethod.subscribe(
            function (method) {
                if (typeof originalOrderButton !== 'undefined') {
                    if (method.method === 'creditkey_gateway') {
                        originalOrderButton.html('<span data-bind="i18n: \'Continue with Credit Key\'">Continue with Credit Key</span>');
                    } else {
                        originalOrderButton.html(originalOrderButtonVal);
                    }
                }
            }, null, 'change'
        );

        return Component.extend(
            {
                defaults: {
                    template: 'CreditKey_B2BGateway/payment/form',
                    transactionResult: ''
                },

                initObservable: function () {
                    this._super();

                    window.addEventListener('message', function (event) {
                        if (event.origin !== window.location.origin) {
                            return;
                        }

                        if (event.data.event === 'creditkey_b2b_gateway' && event.data.action === 'closePopup') {
                            window.addEventListener('message', function (event) {
                                if (event.origin !== window.location.origin) {
                                    return;
                                }

                                if (event.data.event === 'creditkey_b2b_gateway' && event.data.action === 'closePopup') {
                                    setTimeout(() => {
                                        document.getElementById('creditkey-modal').remove();
                                    }, 5000);
                                }
                            });
                        }
                    });

                    return this;
                },

                getPaymentAcceptanceMarkSrc: function () {
                    return window.checkoutConfig.payment.creditkey_gateway.assetSrc;
                },

                getCode: function () {
                    return 'creditkey_gateway';
                },

                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            'transaction-result': ''
                        }
                    }
                },

                getCustomTitle: function () {
                    var totals = quote.getTotals()();
                    var charges = new creditKey.Charges(
                        totals.subtotal,
                        totals.base_shipping_amount,
                        totals.base_tax_amount,
                        totals.base_discount_amount,
                        totals.base_grand_total
                    );
                    originalOrderButton = $('.checkout.primary, .btn-proceed-checkout').last().last();
                    originalOrderButtonVal = originalOrderButton.html();
                },

                isDisplayed: function () {
                    var data = window.checkoutConfig.payment.creditkey_gateway;
                    return data.isCreditKeyDisplayed;
                },

                redirectToPayment: function () {
                    // validate the form
                    if (this.validate() && additionalValidators.validate()) {
                        var checkoutMode = window.checkoutConfig.payment.creditkey_gateway.checkoutMode;
                        // if valide then we call our checkout modal
                        setPaymentInformation(messageContainer, { method: quote.paymentMethod().method })
                        .then(
                            function () {
                                creditKey.checkout(data.redirectUrl, checkoutMode);
                                $('#creditkey-iframe').livequery(
                                    function () {
                                        $('#creditkey-iframe').attr('scrolling','yes');
                                    }
                                );
                            }
                        );
                    }
                },

                redirectAfterPlaceOrder: false,

                selectPaymentMethod: function () {
                    selectPaymentMethodAction(this.getData());
                    checkoutData.setSelectedPaymentMethod(this.item.method);
                    return true;
                }
            }
        );
    }
);
