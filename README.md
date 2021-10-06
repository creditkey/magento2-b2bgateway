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
