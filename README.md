ready2order PHP API
=============

ready2order PHP API v1 is a PHP-wrapper for simply using ready2order API [api.ready2order.com](https://api.ready2order.com)

# Get started
* Make sure you register as a developer first [here](https://api.ready2order.com) to obtain your "Developer Token"
* Use the developer token you received to request access to existing accounts to obtain the "Account Token"
* Use the "Account Token" for your API requests



Installation
------------

You can install this package with composer

```
composer require ready2order/r2o-api-client-php
```


Examples
--------

## Fetch account information 

```php
use \ready2order\Client;

$client = new Client('your-token');
print_r($client->get('company'));
```

## Insert new product group with one product

```php
use \ready2order\Client;

$client = new Client('your-token');

// Insert a new productgroup
$productGroup = $client->put('productgroups',array(
    "productgroup_name" => "Soft drinks"
));
$this->assertArrayHasKey("productgroup_name",$productGroup);


// Insert product
$product = $client->put('products',array(
        "product_name" => "Cupcake",
        "product_price" => "5.2",
        "product_vat" => "20",
        "productgroup" => array(
            "productgroup_id" => $productGroup["productgroup_id"]
        )
));
```
