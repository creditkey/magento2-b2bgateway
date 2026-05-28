/*browser:true*/
/*global define*/
define(
    [
      'jquery',
      'creditkeysdk',
      'CreditKey_B2BGateway/js/view/marketing-url'
    ],
    function ($, creditKey, marketingUrl) {
        'use strict';

        var globalOptions = {
            ckConfig: null
        };

        $.widget(
            'creditkey.marketing', {
                options: globalOptions,

                _init: function initMarketing()
                {
                    var mode = 'production';
                    var elem = this.element;
                    var config = this.options.ckConfig;
                    if(config.endpoint.indexOf('staging') >= 0) {
                        mode = 'staging';
                    }
                    var ckClient = new creditKey.Client(
                        config.publicKey,
                        mode
                    );
                    var charges = new creditKey.Charges(...config.charges);

                    if (charges.data.total <= 0 || charges.data.grand_total <= 0) {
                        return;
                    }

                    var res = marketingUrl.replaceHost(
                        ckClient.get_pdp_display(charges),
                        config.endpoint
                    );
                    elem.html(res);
                }
            }
        );

        return $.creditkey.marketing;
    }
);
