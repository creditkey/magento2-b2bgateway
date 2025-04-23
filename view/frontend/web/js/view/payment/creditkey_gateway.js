/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'creditkey_gateway',
                component: 'CreditKey_B2BGateway/js/view/payment/method-renderer/creditkey_gateway'
            }
        );
        /**
    * Add view logic here if needed 
    */
        return Component.extend({});
    }
);
