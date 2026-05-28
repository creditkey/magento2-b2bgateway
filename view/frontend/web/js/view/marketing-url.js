/*browser:true*/
/*global define*/
define(
    [],
    function () {
        'use strict';

        function getMarketingEndpoint(endpoint)
        {
            var url;

            if (!endpoint) {
                return '';
            }

            try {
                url = new URL(endpoint);
            } catch (e) {
                return endpoint.replace(/\/app\/?$/, '').replace(/\/$/, '');
            }

            if (url.hostname.indexOf('staging') >= 0) {
                return url.protocol + '//staging-marketing.creditkey.com';
            }

            if (url.hostname.indexOf('preview') >= 0) {
                return url.protocol + '//marketing.preview.creditkey.com';
            }

            if (url.hostname === 'www.creditkey.com' || url.hostname === 'creditkey.com') {
                return url.protocol + '//marketing.creditkey.com';
            }

            if (url.hostname.indexOf('marketing') >= 0) {
                return url.origin;
            }

            return url.origin + url.pathname.replace(/\/app\/?$/, '').replace(/\/$/, '');
        }

        return {
            replaceHost: function (html, endpoint) {
                var marketingEndpoint = getMarketingEndpoint(endpoint);

                if (!marketingEndpoint) {
                    return html;
                }

                return html.replace(/http:\/\/localhost:3002/g, marketingEndpoint);
            }
        };
    }
);
