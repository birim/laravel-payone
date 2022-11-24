# PAYONE Payment Gateway for Laravel

Official PAYONE documentation: https://docs.payone.com

Note: The code is not audited for security and comes with no guarantee whatsoever.

## Installation

You can install the package via composer:
```
composer require birim/laravel-payone
```
You can publish the config file with:
```
php artisan vendor:publish --tag=laravel-payone
```
This is the contents of the published config file:
```
return [

    // PAYONE API Version
    'version' => '3.11',

    // Mode for transactions, either ‘live’ or ‘test’
    'mode' => 'test',

    // The type of character encoding used in the request.
    'encoding' => 'UTF-8',

    // Name of the developer
    'integrator_name' => '',

    // Sub-Account ID, defined by PAYONE
    'sub_account_id' => '',

    // Portal ID, defined by PAYONE
    'portal_id' => '',

    // Merchant ID, defined by PAYONE
    'merchant_id' => '',

    // Payment portal key as MD5 value
    'key' => ''
];

```

## Changelog

Please read [CHANGELOG](CHANGELOG.md) for more information of what was changed recently.

## Example of use

### Initiating payment reservation (preauthorization)

 ```
 Payone::sendRequest([
    'request' => 'preauthorization',
    'clearingtype' => 'cc',
    'reference' => uniqid(),
    'amount' => 1500,
    'currency' => 'EUR',
    'lastname' => 'CUSTOMERS_LASTNAME',
    'country' => 'DE',
    'items' => [
        [
            'id' => 1,
            'type' => 'goods',
            'sku' => 'QL-NBB-477-48',
            'price' => 1500,
            'description' => 'Lorem ipsum'
        ]
    ],
    'successurl' => route('payone_success_url'),
    'errorurl' => route('payone_error_url'),
    'backurl' => route('payone_back_url'),
    'pseudocardpan' => $pseudoCardPan
 ]);
 ```

### Creating a contract (createaccess)

 ```
 Payone::sendRequest([
    'aid' => Payone::getSubAccountId(),
    'request' => 'createaccess',
    'clearingtype' => 'elv',
    'reference' => uniqid(),
    'productid' => $productId,
    'settle_period_length' => 1,
    'settle_period_unit' => 'M',
    'lastname' => 'CUSTOMERS_LASTNAME',
    'country' => 'DE',
    'bankcountry' => 'DE',
    'iban' => '2599100003'
 ]);
 ```

## Override PAYONE API Configuration

 ```
 Payone::setSubAccountId(12345)
 ```
