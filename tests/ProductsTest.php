<?php

declare(strict_types=1);

namespace ready2order\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use ready2order\Exceptions\ErrorResponseException;

/**
 * @internal
 */
#[CoversNothing]
class ProductsTest extends AbstractTestCase
{
    public function testGetProductList(): void
    {
        $products = $this->getApiClient()->get('products');
        self::assertNotEmpty($products);
        self::assertArrayHasKey('product_name', $products[0]);
    }

    public function testInsertProduct(): void
    {
        $client = $this->getApiClient();

        $productGroup = null;
        $product = null;
        $cleanedUp = false;

        try {
            // Insert productGroup
            $productGroup = $client->put('productgroups', [
                'productgroup_name' => 'PHPUnit Testproductgroup',
            ]);
            self::assertArrayHasKey('productgroup_name', $productGroup);

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
                'includeProductGroup' => true,
            ]);
            self::assertEquals($product['product_id'], $fetchedProduct['product_id']);
            self::assertEquals($product['product_price'], $fetchedProduct['product_price']);

            self::assertArrayHasKey('product_name', $product);
            self::assertArrayHasKey('productgroup', $fetchedProduct);

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
            self::assertArrayHasKey('product_name', $product);
            self::assertEquals($testValues['product_price'], $product['product_price']);
            self::assertEquals($testValues['product_vat'], $product['product_vat']);
            self::assertEquals($testValues['product_stock_value'], $product['product_stock_value']);
            self::assertEquals($testValues['product_stock_enabled'], $product['product_stock_enabled']);

            // Update product with bad values
            $exceptionThrown = false;

            try {
                $product = $client->post("products/{$product['product_id']}", ['product_price' => 'bad price', 'product_vat' => 'bad value', 'product_stock_enabled' => 5, 'product_stock_value' => 'bad value']);
            } catch (ErrorResponseException $e) {
                $errorBag = $e->getData()['details']['errors'];
                self::assertArrayHasKey('product_price', $errorBag);
                self::assertArrayHasKey('product_vat', $errorBag);
                self::assertArrayHasKey('product_stock_enabled', $errorBag);
                self::assertArrayHasKey('product_stock_value', $errorBag);

                $exceptionThrown = true;
            }

            self::assertTrue($exceptionThrown);

            // Testing again good values
            $product = $client->post("products/{$product['product_id']}", ['product_description' => $testValues['product_description'], 'product_itemnumber' => $testValues['product_itemnumber'], 'product_barcode' => $testValues['product_barcode']]);
            self::assertArrayHasKey('product_name', $product);
            self::assertEquals($testValues['product_description'], $product['product_description']);
            self::assertEquals($testValues['product_itemnumber'], $product['product_itemnumber']);
            self::assertEquals($testValues['product_barcode'], $product['product_barcode']);

            // Delete product
            $deleted = $client->delete("products/{$product['product_id']}");
            self::assertTrue($deleted['success']);

            // Delete productgroup
            $deleted = $client->delete("productgroups/{$productGroup['productgroup_id']}");
            self::assertTrue($deleted['success']);

            $cleanedUp = true;
        } finally {
            // Best-effort cleanup so a mid-test failure does not orphan test data.
            if (!$cleanedUp) {
                if ($product !== null) {
                    $client->delete("products/{$product['product_id']}");
                }

                if ($productGroup !== null) {
                    $client->delete("productgroups/{$productGroup['productgroup_id']}");
                }
            }
        }
    }
}
