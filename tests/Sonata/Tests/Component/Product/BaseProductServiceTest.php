<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Tests\Component\Product;

use Sonata\ProductBundle\Model\BaseProductProvider;
use Sonata\OrderBundle\Entity\BaseOrderElement;
use Sonata\Component\Basket\BasketElement;
use Sonata\Component\Order\OrderInterface;

use Sonata\ProductBundle\Entity\BaseProduct;

class Product extends BaseProduct
{
    public $enabled = true;
    public $id = 1;
    public $name = 'fake name';
    public $price = 15;
    public $vat = 19.6;

    public function isRecurrentPayment()
    {
        return false;
    }

    public function getElementOptions()
    {
        return array();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        return $this->id = $id;
    }
}

class OrderElement extends BaseOrderElement
{

}

class BaseProductServiceTest_ProductProvider extends BaseProductProvider
{
    /**
     * @return string
     */
    public function getBaseControllerName()
    {
        // TODO: Implement getBaseControllerName() method.
    }
}

class BaseOrderElementTest_ProductProvider extends BaseOrderElement
{

}

class BaseProductServiceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @return BaseProductServiceTest_ProductProvider
     */
    public function getBaseProvider()
    {
        $serializer = $this->getMock('JMS\Serializer\SerializerInterface');
        $serializer->expects($this->any())->method('serialize')->will($this->returnValue('{}'));

        $provider = new BaseProductServiceTest_ProductProvider($serializer);

        $basketElementManager = $this->getMock('\Sonata\Component\Basket\BasketElementManagerInterface');
        $basketElementManager->expects($this->any())->method('getClass')->will($this->returnValue('\Sonata\Tests\Component\Product\BaseOrderElementTest_ProductProvider'));

        $provider->setBasketElementManager($basketElementManager);

        $provider->setOrderElementClassName(get_class(new OrderElement()));

        return $provider;
    }

    public function testOptions()
    {
        $provider = $this->getBaseProvider();

        $this->assertInternalType('array', $provider->getOptions());
        $this->assertNull($provider->getOption('foo'));
        $provider->setOptions(array('foo' => 'bar'));

        $this->assertEquals('bar', $provider->getOption('foo'));
    }

    public function testOrderElement()
    {
        $product = $this->getMock('Sonata\Component\Product\ProductInterface');
        $product->expects($this->any())->method('getId')->will($this->returnValue(42));
        $product->expects($this->any())->method('getName')->will($this->returnValue('Product name'));
        $product->expects($this->any())->method('getPrice')->will($this->returnValue(9.99));
        $product->expects($this->any())->method('getOptions')->will($this->returnValue(array('foo' => 'bar')));
        $product->expects($this->any())->method('getDescription')->will($this->returnValue('product description'));

        $basketElement = new BasketElement();
        $basketElement->setProduct('product_code', $product);

        $provider = $this->getBaseProvider();

        $orderElement = $provider->createOrderElement($basketElement);

        $this->assertInstanceOf('Sonata\Component\Order\OrderElementInterface', $orderElement);
        $this->assertEquals(OrderInterface::STATUS_PENDING, $orderElement->getStatus());
        $this->assertEquals('Product name', $orderElement->getDesignation());
        $this->assertEquals(1, $orderElement->getQuantity());
    }

    public function testVariation()
    {
        $provider = $this->getBaseProvider();

        $this->assertInternalType('array', $provider->getVariationFields());

        $provider->setVariationFields(array('name', 'price'));

        $this->assertTrue($provider->hasVariationFields(), '::hasVariationFields() return true' );
        $this->assertTrue($provider->isVariateBy('name'), '::isVariateBy() return true for existing field');
        $this->assertFalse($provider->isVariateBy('fake'), '::isVariateBy() return false for non existing field');
        $this->assertInternalType('array', $provider->getVariationFields());
    }

    public function testProductDuplicate()
    {
        $provider = $this->getBaseProvider();
        $provider->setVariationFields(array('name', 'price'));

        $product = new Product;
        $product->id = 2;

        $variation = $provider->createVariation($product);
        $variation->setPrice(11);
        $product->addVariation($variation);

        $this->assertNull($variation->getId());
        $this->assertEquals('fake name (duplicated)', $variation->getName(), '::getName() return the duplicated name');

        $variation = $provider->createVariation($product);
        $variation->setPrice(12);
        $product->addVariation($variation);

        $variation = $provider->createVariation($product);
        $variation->setPrice(13);
        $product->addVariation($variation);

        $this->assertEquals(count($product->getVariations()), 3,  '::getVariations() returns 3 elements');

        $product->setName('test');
        $product->setVat(5.5);
        $product->setPrice(4);

        // copy the information into the variation
        $provider->copyVariation($product, 'product');

        $this->assertEquals(4, $product->getPrice(), '::getPrice() return 4');

        $variations = $product->getVariations();

        // price should be unchanged
        $this->assertEquals(11, $variations[0]->getPrice(), '::getPrice() return 11');
        $this->assertEquals(12, $variations[1]->getPrice(), '::getPrice() return 12');
        $this->assertEquals(13, $variations[2]->getPrice(), '::getPrice() return 13');

        // vat should be updated
        $this->assertEquals(5.5, $variations[0]->getVat(), '::getVat() return 5.5');
        $this->assertEquals(5.5, $variations[1]->getVat(), '::getVat() return 5.5');
        $this->assertEquals(5.5, $variations[2]->getVat(), '::getVat() return 5.5');

        // copy the information into the variation, and force price update
        $provider->copyVariation($product, 'product', true);

        $variations = $product->getVariations();

        // price should be changed
        $this->assertEquals(4, $variations[0]->getPrice(), '::getPrice() return 4');
        $this->assertEquals(4, $variations[1]->getPrice(), '::getPrice() return 4');
        $this->assertEquals(4, $variations[2]->getPrice(), '::getPrice() return 4');
    }

    public function testArrayProduct()
    {
        $product = new Product;

        $arrayProduct = array(
            'sku'                  => 'productSku',
            'slug'                 => 'productSlug',
            'name'                 => 'productName',
            'description'          => 'productDescription',
            'rawDescription'       => 'productRawDescription',
            'descriptionFormatter' => 'productDescriptionFormatter',
            'price'                => 123.45,
            'vat'                  => 678.90,
            'stock'                => 12345,
            'enabled'              => 1,
            'options'              => array('key1' => 'value1', 'key2' => array('value2', 'value3')),
        );

        $product->fromArray($arrayProduct);

        $this->assertEquals($arrayProduct, $product->toArray());

        $this->assertEquals($product->getSku(),                  $arrayProduct['sku']);
        $this->assertEquals($product->getSlug(),                 $arrayProduct['slug']);
        $this->assertEquals($product->getName(),                 $arrayProduct['name']);
        $this->assertEquals($product->getDescription(),          $arrayProduct['description']);
        $this->assertEquals($product->getRawDescription(),       $arrayProduct['rawDescription']);
        $this->assertEquals($product->getDescriptionFormatter(), $arrayProduct['descriptionFormatter']);
        $this->assertEquals($product->getPrice(),                $arrayProduct['price']);
        $this->assertEquals($product->getVat(),                  $arrayProduct['vat']);
        $this->assertEquals($product->getStock(),                $arrayProduct['stock']);
        $this->assertEquals($product->getEnabled(),              $arrayProduct['enabled']);
        $this->assertEquals($product->getOptions(),              $arrayProduct['options']);
    }
}
