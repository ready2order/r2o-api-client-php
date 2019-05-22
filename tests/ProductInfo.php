<?php

use \ready2order\ready2orderAPI;
use ready2order\ready2orderErrorException;

class ProductInfoTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $dotenv = new Dotenv\Dotenv(__DIR__.'/../');
        $dotenv->load();
    }

    public function testGetProductList()
    {
        $ready2order = new ready2orderAPI(getenv('R2O_USER_TOKEN'));
        $ready2order->setApiEndpoint(getenv('R2O_API'));
        $info = $ready2order->get('products');
        if(array_key_exists(0,$info)){
            $this->assertArrayHasKey("product_name",$info[0]);
        }
    }

    public function testInsertProduct()
    {
        $ready2order = new ready2orderAPI(getenv('R2O_USER_TOKEN'));
        $ready2order->setApiEndpoint(getenv('R2O_API'));

        // INSERT PRODUCTGROUP
        $productGroup = $ready2order->put('productgroups',array(
            "productgroup_name" => "PHPUnit Testproductgroup"
        ));
        $this->assertArrayHasKey("productgroup_name",$productGroup);


        // INSERT PRODUCT
        $product = $ready2order->put('products',array(
                "product_name" => "PHPUnit Testproduct",
                "product_price" => 20.09,
                "product_vat" => 20,
                "productgroup" => array(
                    "productgroup_id" => $productGroup["productgroup_id"]
                )
        ));
        $fetchedProduct = $ready2order->get("products/{$product["product_id"]}");
        $this->assertEquals($product["product_id"],$fetchedProduct["product_id"]);
        $this->assertEquals($product["product_price"],$fetchedProduct["product_price"]);

        $this->assertArrayHasKey("product_name",$product);

        // UPDATE PRODUCT
        // TEST FOR NORMAL VALUES
        $testValues = array();
        $testValues["product_vat"] = 10;
        $testValues["product_price"] = 2009.91;
        $testValues["product_stock_value"] = 12.5;
        $testValues["product_stock_enabled"] = false;
        $testValues["product_description"] = "ready2order API tested successfully!";
        $testValues["product_itemnumber"] = "PHP15XX";
        $testValues["product_barcode"] = "1234567890";
        // START TEST
        $product = $ready2order->post("products/{$product["product_id"]}",array("product_price"=>$testValues["product_price"],"product_vat"=>$testValues["product_vat"],"product_stock_enabled"=>$testValues["product_stock_enabled"], "product_stock_value" => $testValues["product_stock_value"]));
        $this->assertArrayHasKey("product_name",$product);
        $this->assertEquals($testValues["product_price"],$product["product_price"]);
        $this->assertEquals($testValues["product_vat"],$product["product_vat"]);
        $this->assertEquals($testValues["product_stock_value"],$product["product_stock_value"]);
        $this->assertEquals($testValues["product_stock_enabled"],$product["product_stock_enabled"]);


        // UPDATE PRODUCT
        // TEST FOR BAD VALUES
        $exceptionThrown = false;
        try {
            $product = $ready2order->post("products/{$product["product_id"]}", array("product_price" => "bad price", "product_vat" => "bad value", "product_stock_enabled" => 5, "product_stock_value" => "bad value"));
        } catch (ready2orderErrorException $e){
            $errorBag = $e->getData()["details"]["errors"];
            $this->assertArrayHasKey("product_price", $errorBag);
            $this->assertArrayHasKey("product_vat", $errorBag);
            $this->assertArrayHasKey("product_stock_enabled", $errorBag);
            $this->assertArrayHasKey("product_stock_value", $errorBag);

            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

        // TESTING AGAIN GOOD VALUES
        $product = $ready2order->post("products/{$product["product_id"]}",array("product_description"=>$testValues["product_description"],"product_itemnumber"=>$testValues["product_itemnumber"],"product_barcode"=>$testValues["product_barcode"]));
        $this->assertArrayHasKey("product_name",$product);
        $this->assertEquals($testValues["product_description"],$product["product_description"]);
        $this->assertEquals($testValues["product_itemnumber"],$product["product_itemnumber"]);
        $this->assertEquals($testValues["product_barcode"],$product["product_barcode"]);

        // DELETE PRODUCT
        $deleted = $ready2order->delete("products/{$product["product_id"]}");
        $this->assertEquals(true,$deleted["success"]);

        // DELETE PRODUCTGROUP
        $deleted = $ready2order->delete("productgroups/{$productGroup["productgroup_id"]}");
        $this->assertEquals(true,$deleted["success"]);
    }



}