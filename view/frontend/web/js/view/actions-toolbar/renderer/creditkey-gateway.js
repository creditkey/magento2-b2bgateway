/**
 * Copyright 2019 aheadWorks. All rights reserved.\nSee LICENSE.txt for license details.
 */

define(
    [
    'Aheadworks_OneStepCheckout/js/view/actions-toolbar/renderer/default'
    ], function (Component) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'CreditKey_B2BGateway/actions-toolbar/renderer/creditkey-gateway'
                },

                /**
                 * Redirect to CreditKey
                 */
                redirectToPayment: function () {
                    var self = this;

                    this._beforeAction().done(
                        function () {
                            self._getMethodRenderComponent().redirectToPayment();
                        }
                    );
                }
            }
        );
    }
);
