<?php

declare(strict_types=1);

namespace Tests;

use ready2order\Exceptions\ErrorResponseException;

/**
 * @internal
 * @coversNothing
 */
class ProductsTest extends AbstractTestCase
{
    public function testGetProductList(): void
    {
        $products = $this->getApiClient()->get('products');
        $this->assertNotEmpty($products);
        $this->assertArrayHasKey('product_name', $products[0]);
    }

    public function testInsertProduct(): void
    {
        $client = $this->getApiClient();

        // Insert productGroup
        $productGroup = $client->put('productgroups', [
            'productgroup_name' => 'PHPUnit Testproductgroup',
        ]);
        $this->assertArrayHasKey('productgroup_name', $productGroup);

        // Insert product
        $product = $client->put('products', [
            'product_name' => 'PHPUnit Testproduct',
            'product_price' => '120.00',
            'product_vat' => '20',
            'productgroup' => [
                'productgroup_id' => $productGroup['productgroup_id'],
            ],
        ]);
        $fetchedProduct = $client->get("products/{$product['product_id']}", [
            'includeProductGroup' => true
        ]);
        $this->assertEquals($product['product_id'], $fetchedProduct['product_id']);
        $this->assertEquals($product['product_price'], $fetchedProduct['product_price']);

        $this->assertArrayHasKey('product_name', $product);
        $this->assertArrayHasKey('productgroup', $fetchedProduct);

        // Update product with good values
        $testValues = [];
        $testValues['product_vat'] = 10;
        $testValues['product_price'] = '220';
        $testValues['product_stock_value'] = 12.5;
        $testValues['product_stock_enabled'] = false;
        $testValues['product_description'] = 'ready2order API tested successfully!';
        $testValues['product_itemnumber'] = 'PHP15XX';
        $testValues['product_barcode'] = '1234567890';

        $product = $client->post("products/{$product['product_id']}", ['product_price' => $testValues['product_price'], 'product_vat' => $testValues['product_vat'], 'product_stock_enabled' => $testValues['product_stock_enabled'], 'product_stock_value' => $testValues['product_stock_value']]);
        $this->assertArrayHasKey('product_name', $product);
        $this->assertEquals($testValues['product_price'], $product['product_price']);
        $this->assertEquals($testValues['product_vat'], $product['product_vat']);
        $this->assertEquals($testValues['product_stock_value'], $product['product_stock_value']);
        $this->assertEquals($testValues['product_stock_enabled'], $product['product_stock_enabled']);

        // Update product with bad values
        $exceptionThrown = false;

        try {
            $product = $client->post("products/{$product['product_id']}", ['product_price' => 'bad price', 'product_vat' => 'bad value', 'product_stock_enabled' => 5, 'product_stock_value' => 'bad value']);
        } catch (ErrorResponseException $e) {
            $errorBag = $e->getData()['details']['errors'];
            $this->assertArrayHasKey('product_price', $errorBag);
            $this->assertArrayHasKey('product_vat', $errorBag);
            $this->assertArrayHasKey('product_stock_enabled', $errorBag);
            $this->assertArrayHasKey('product_stock_value', $errorBag);

            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

        // Testing again good values
        $product = $client->post("products/{$product['product_id']}", ['product_description' => $testValues['product_description'], 'product_itemnumber' => $testValues['product_itemnumber'], 'product_barcode' => $testValues['product_barcode']]);
        $this->assertArrayHasKey('product_name', $product);
        $this->assertEquals($testValues['product_description'], $product['product_description']);
        $this->assertEquals($testValues['product_itemnumber'], $product['product_itemnumber']);
        $this->assertEquals($testValues['product_barcode'], $product['product_barcode']);

        // Delete product
        $deleted = $client->delete("products/{$product['product_id']}");
        $this->assertTrue($deleted['success']);

        // Delete productgroup
        $deleted = $client->delete("productgroups/{$productGroup['productgroup_id']}");
        $this->assertTrue($deleted['success']);
    }
}
