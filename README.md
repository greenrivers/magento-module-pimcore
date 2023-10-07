# Greenrivers PimcoreIntegration module

### Installation

1. Copy the contents of this module to `app/code/Greenrivers/PimcoreIntegration`
2. Run `php bin/magento module:enable Greenrivers_PimcoreIntegration`
3. Run `php bin/magento setup:upgrade` & `php bin/magento setup:di:compile`

### Config

_Stores->Configuration->Greenrivers->PimcoreIntegration_

_General Settings_

**Enable Integration** - enable/disable integration<br>
**Pimcore Url** - Pimcore base url (ending with slash)<br>
**Pimcore Api Key** - Pimcore api key from GraphQL DataHub

_Magento_

**Override Product** - override product in Magento<br>
**Override Category** - override category in Magento

### Usage

```shell
php bin/magento greenrivers:pimcore:sync --products --categories
```

1. Get products & categories from Pimcore.
2. Push into message queues.
3. Run message queues to save products & categories in Magento.

### Endpoints

https://app.magento.test/graphql

Authorization: Bearer Token **(integration token)**

Create product

```graphql
mutation {
    createProduct(
        input: {
            status: 1
            attribute_set_id: 4
            name: "Product test"
            sku: "test"
            price: 23.99
        }
    ) {
        product {
            status
            attribute_set_id
            name
            sku
            price
        }
    }
}
```

Create category

```graphql
mutation {
    createCategory(
        input: {
            is_active: 1
            include_in_menu: 1
            name: "Category test"
            parent_id: 2
        }
    ) {
        category {
            is_active
            include_in_menu
            name
            parent_id
        }
    }
}
```

### Errors

Errors are logged into **var/log/greenrivers/pimcore_integration.log** folder.

### Testing

Run unit tests from root project directory:

```shell
./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Greenrivers/PimcoreIntegration
```

Run integration tests from root project directory:

```shell
vendor/bin/phpunit -c $(pwd)/dev/tests/integration/phpunit.xml app/code/Greenrivers/PimcoreIntegration/Test/Integration
```

Run API functional tests from root project directory:

(due to: https://github.com/magento/magento2/issues/36291 & https://github.com/magento/magento2/issues/33696 update database config to tested db in **app/etc/env.php**)

```shell
vendor/bin/phpunit -c $(pwd)/dev/tests/api-functional/phpunit_graphql.xml app/code/Greenrivers/PimcoreIntegration/Test/Api
```

### Sources

https://pimcore.com/docs/platform/Datahub/GraphQL/#external-access

https://developer.adobe.com/commerce/webapi/get-started/authentication/gs-authentication-token/#integration-tokens
