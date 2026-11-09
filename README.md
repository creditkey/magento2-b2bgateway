# Credit Key Magento 2 Module

## Installation

From your root Magento directory, run the following command:

```
% composer require creditkey/b2bgateway
```

Then enable the module with the following commands:
```
% php bin/magento module:enable CreditKey_B2BGateway
% php bin/magento setup:upgrade
```

## Configuration

From the Magento admin, navigate to ```Stores > Configuration > Sales > Payment Methods``` and scroll down to the ```Credit Key (Gateway)``` section.

The `Marketing Content on Product Pages` section allows you to enable the Credit Key marketing content to be displayed on the selected product detail pages. You can enable/disable this feature globally, select the specific categories to allow the content to be displayed on the products belonging to said categories, and select the style of the displayed content.

Payment Action can be set to ```Authorize``` or ```Authorize and Capture```. ```Authorize and Capture``` will create an invoice when an order is created. ```Authorize``` will not create an invoice when an order is created.

## Pay by Bank

Requires `creditkey/creditkey-php ^1.0.8`. In **Stores > Configuration > Sales > Payment Methods > Credit Key (Gateway)**, check **Enable Pay by Bank** and save. The checkbox defaults to off and supports website/store inheritance.

Checkout displays **Pay Over Time** and, when enabled, **Pay by Bank** with the description **Pay directly from your bank account**. Both use the bundled Credit Key logo. Pay Over Time currently uses “Net 30 or monthly payments”; no fixed monthly quote is advertised.

Pay by Bank inherits Credit Key's enabled status, minimum checkout price, applicable countries, API eligibility, credentials, checkout mode, payment action, and order-processing commands. It is a storefront method (`creditkey_pay_by_bank`); the existing CSR method remains separate. Pay by Bank orders retain their method code for capture, refund, shipment, and order-total updates.

The create controller reads the payment method saved on the quote. Only Pay by Bank sends `merchant_data: {"pay_now": true}`. Standard Credit Key omits merchant data. Availability and eligibility are checked again before starting checkout, including after configuration or cart changes.

This renderer targets standard Magento checkout and includes the existing Aheadworks renderer registration. The separate Hyvä module needs its own renderer for the second payment option.

### Regression tests

With an installed Magento environment and PHPUnit 12 available, run:

```bash
MAGENTO_ROOT=/path/to/magento /path/to/phpunit --no-configuration --bootstrap Test/Unit/bootstrap.php Test/Unit
```

Tests cover store-scoped configuration inheritance, both enable flags, minimum boundaries for both methods, eligibility/country rejection, unsupported payment methods, and boolean Pay by Bank metadata. They do not contact the API.

Run the renderer regression tests with Node.js:

```bash
node --test Test/Unit/payment-renderer.test.cjs
```

These cover method switching, modal replacement, delayed cancellation, failed saves, validation, and duplicate clicks. Each successful checkout attempt opens a fresh flow so the JavaScript SDK cannot reuse the previous payment method's hidden modal.

Before release, verify both modal and redirect checkout, returning customers and guests, disabling the checkbox, amounts below/equal to the minimum, cancellation/retry, and completed-order capture/refund in a staging environment. Deploy with the usual Magento dependency-injection compilation, static-content deployment, and cache-clean steps.

## Customization

To move the location of the marketing display on the product details page you will need to modify the file `catalog_product_view.xml` from your active theme. This will most likely be located at `{magento_root}/app/design/frontend/{YourCompany}/{theme-name}/Magento_Catalog/layout/catalog_product_view.xml`. 

In this file you would simply use the `<move>` element to move our block, named `product.info.creditkey.marketing`, to the new location. For example, if you wanted it to be just below the "Add to Cart" button it would look something like the following where `element` is the name of our block, `destination` is the name of the container you are moving it into, and `after` is the name of the block or container it will go after. This could instead be `before` if you want to place it before a specific block/container.

    <?xml version="1.0"?>
    <page layout="1column" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
        <body>
            <move element="product.info.creditkey.marketing" 
                    destination="product.info.form.content" 
                    after="product.info.addtocart"/>
        </body>
    </page>

To see the available containers you can use reference Magento's primary `catalog_product_view.xml` file, located at `vendor/magento/module-catalog/view/frontend/layout/catalog_product_view.xml`.
