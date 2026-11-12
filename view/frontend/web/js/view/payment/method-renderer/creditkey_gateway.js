/*browser:true*/
/*global define*/
define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/set-payment-information',
    'creditkeysdk',
    'CreditKey_B2BGateway/js/jquery.livequery.min'
], function ($, Component, selectPaymentMethodAction, checkoutData, additionalValidators,
    setPaymentInformation, creditKey) {
    'use strict';

    var config = window.checkoutConfig.payment.creditkey_gateway;
    // Both methods use the same merchant and API environment.
    new creditKey.Client(config.publicKey, config.endpoint);

    window.addEventListener('message', function (event) {
        if (event.origin === window.location.origin && event.data &&
            event.data.event === 'creditkey_b2b_gateway' && event.data.action === 'closePopup') {
            var modal = document.getElementById('creditkey-modal');
            setTimeout(function () {
                if (modal) {
                    modal.remove();
                }
            }, 5000);
        }
    });

    return Component.extend({
        defaults: {
            template: 'CreditKey_B2BGateway/payment/form',
            redirectAfterPlaceOrder: false
        },

        getCode: function () {
            return this.item.method;
        },

        getMethodConfig: function () {
            return window.checkoutConfig.payment[this.getCode()];
        },

        getTitle: function () {
            return this.getCode() === 'creditkey_pay_by_bank' ?
                'Credit Key — BUSINESS Pay by Bank' : 'Credit Key — BUSINESS Pay Over Time';
        },

        getPaymentAcceptanceMarkSrc: function () {
            return this.getMethodConfig().assetSrc;
        },

        isDisplayed: function () {
            return this.getMethodConfig().isCreditKeyDisplayed;
        },

        getData: function () {
            return {method: this.getCode()};
        },

        redirectToPayment: function () {
            var data = this.getMethodConfig(), self = this;
            if (this.isPlaceOrderActionAllowed() && this.validate() && additionalValidators.validate()) {
                // Persist the selected method before the server decides whether to send pay_now.
                this.isPlaceOrderActionAllowed(false);
                return setPaymentInformation(this.messageContainer, {method: this.getCode()}).then(function () {
                    // The SDK reuses hidden modals for identical URLs. Both methods share
                    // this route, so discard the previous flow after saving the selection.
                    var modal = document.getElementById('creditkey-modal');
                    if (modal) {
                        modal.remove();
                    }
                    var checkoutUrl = data.redirectUrl + (data.redirectUrl.indexOf('?') === -1 ? '?' : '&') +
                        '_ck_attempt=' + Date.now();
                    creditKey.checkout(checkoutUrl, data.checkoutMode);
                    $('#creditkey-iframe').livequery(function () {
                        $(this).attr('scrolling', 'yes');
                    });
                }).always(function () {
                    self.isPlaceOrderActionAllowed(true);
                });
            }
            return false;
        },

        selectPaymentMethod: function () {
            selectPaymentMethodAction(this.getData());
            checkoutData.setSelectedPaymentMethod(this.getCode());
            return true;
        }
    });
});
