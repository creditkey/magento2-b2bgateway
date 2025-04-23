/*browser:true*/
/*global define*/
define(
    [
    'jquery',
    'creditkeysdk'
    ], function ( $, creditKey ) {
        'use strict';

        var globalOptions = {
            ckConfig: null
        };

        $.widget(
            'creditkey.marketing_config', {
                options: globalOptions,

                _init: function initMarketing()
                {
                    var elem = this.element;
                    var view = ($(elem).attr('id').indexOf('checkout') > 0) ? 'checkout' : 'pdp';
            
                    var ckClient = new creditKey.Client(
                        this.options.ckConfig.publicKey,
                        this.options.ckConfig.endpoint
                    );
                    var charges = new creditKey.Charges(0,0,0,0,0);
    
                    ckClient.get_marketing_display(charges, view, $(elem).val(), 'small')
                    .then(
                        function (res) {
                            $('label[for="' + $(elem).attr('id') + '"] span').html(res);
                        }
                    );
                }
            }
        );

        return $.creditkey.marketing_config;
    }
);
