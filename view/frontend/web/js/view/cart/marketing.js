/*browser:true*/
/*global define*/
define(
    [
      'jquery',
      'creditkeysdk'
    ],
    function ($, creditKey) {
        'use strict';

        var globalOptions = {
            ckConfig: null
        };

        $.widget('creditkey.marketing', {
            options: globalOptions,

            _init: function initMarketing() {
                var elem = this.element;
                var config = this.options.ckConfig;
                var ckClient = new creditKey.Client(
                    config.publicKey,
                    config.endpoint
                );
                var charges = new creditKey.Charges(...config.charges);

                var res = ckClient.get_cart_display(charges, config.desktop, config.mobile)
                elem.html(res);
            }
        });

        return $.creditkey.marketing;
    }
);
