<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Component\Tests\Basket;

use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Sonata\Component\Basket\Basket;
use Sonata\Component\Basket\BasketElement;
use Sonata\Component\Basket\BasketElementInterface;
use Sonata\Component\Currency\Currency;
use Sonata\Component\Currency\CurrencyPriceCalculator;
use Sonata\Component\Customer\AddressInterface;
use Sonata\Component\Customer\CustomerInterface;
use Sonata\Component\Delivery\ServiceDeliveryInterface;
use Sonata\Component\Payment\PaymentInterface;
use Sonata\Component\Product\Pool;
use Sonata\Component\Product\ProductDefinition;
use Sonata\Component\Product\ProductInterface;
use Sonata\Component\Product\ProductManagerInterface;
use Sonata\Component\Product\ProductProviderInterface;
use Sonata\Component\Tests\Product\Product;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BasketTest extends TestCase
{
    public function getMockProduct()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->setMockClassName('BasketTest_Product')
            ->getMock();
        $product->expects(static::any())->method('getId')->willReturn(42);
        $product->expects(static::any())->method('getName')->willReturn('Product name');
        $product->expects(static::any())->method('getPrice')->willReturn(15);
        $product->expects(static::any())->method('isPriceIncludingVat')->willReturn(false);
        $product->expects(static::any())->method('getVatRate')->willReturn(19.6);
        $product->expects(static::any())->method('getOptions')->willReturn(['foo' => 'bar']);
        $product->expects(static::any())->method('getDescription')->willReturn('product description');
        $product->expects(static::any())->method('getEnabled')->willReturn(true);

        return $product;
    }

    public function getMockAddress()
    {
        $address = $this->getMockBuilder(AddressInterface::class)
            ->setMockClassName('BasketTest_Address')
            ->getMock();
        $address->expects(static::any())->method('getName')->willReturn('Product name');
        $address->expects(static::any())->method('getAddress1')->willReturn('Address1');
        $address->expects(static::any())->method('getAddress2')->willReturn('Address2');
        $address->expects(static::any())->method('getAddress3')->willReturn('Address3');
        $address->expects(static::any())->method('getPostcode')->willReturn('75001');
        $address->expects(static::any())->method('getCity')->willReturn('Paris');
        $address->expects(static::any())->method('getCountryCode')->willReturn('FR');
        $address->expects(static::any())->method('getPhone')->willReturn('0123456789');

        return $address;
    }

    public function testTotal(): void
    {
        $currency = $this->createMock(Currency::class);

        $basket = new Basket();
        $basket->setCurrency($currency);

        $manager = $this->createMock(ProductManagerInterface::class);
        $manager->expects(static::any())->method('getClass')->willReturn('BasketTest_Product');

        $productProvider = new ProductProviderTest($this->createMock(SerializerInterface::class));
        $productProvider->setCurrencyPriceCalculator(new CurrencyPriceCalculator());
        $productProvider->setEventDispatcher($this->createMock(EventDispatcherInterface::class));

        $productDefinition = new ProductDefinition($productProvider, $manager);

        $product = $this->getMockProduct();

        $product->expects(static::any())
            ->method('isRecurrentPayment')
            ->willReturn(false);

        $pool = new Pool();
        $pool->addProduct('product_code', $productDefinition);

        $basket->setProductPool($pool);

        static::assertFalse($basket->hasProduct($product), '::hasProduct() - The product is not present in the basket');

        $basketElement = new BasketElement();
        $basketElement->setProductDefinition($productDefinition);
        $basketElement->setProduct('product_code', $product);

        $basket->addBasketElement($basketElement);

        static::assertTrue($basket->hasProduct($product), '::hasProduct() - The product is present in the basket');

        static::assertSame(1, $basketElement->getQuantity(), '::getQuantity() - return 1');
        static::assertSame('15', $basketElement->getUnitPrice(false), '::getQuantity() - return 2');
        static::assertSame(
            0,
            bccomp('15', $basketElement->getTotal(false)),
            '::getQuantity() - return 2'
        );

        static::assertSame('15.000', $basket->getTotal(false), '::getTotal() w/o vat return 15');
        static::assertSame('17.940', $basket->getTotal(true), '::getTotal() w/ vat return 18');

        $basketElement->setQuantity(2);

        static::assertSame(2, $basketElement->getQuantity(), '::getQuantity() - return 2');
        static::assertSame('15', $basketElement->getUnitPrice(false), '::getQuantity() - return 2');
        static::assertSame(
            0,
            bccomp('30', $basketElement->getTotal(false)),
            '::getQuantity() - return 2'
        );
        static::assertSame('30.000', $basket->getTotal(false), '::getTotal() w/o vat return 30');
        static::assertSame('35.880', $basket->getTotal(true), '::getTotal() w/ vat return true');

        // Recurrent payments
        static::assertSame(
            '0.000',
            $basket->getTotal(false, true),
            '::getTotal() for recurrent payments only'
        );

        $newProduct = $this->getMockProduct();
        $newProduct->expects(static::any())
            ->method('isRecurrentPayment')
            ->willReturn(true);

        $basketElement = new BasketElement();
        $basketElement->setProduct('product_code', $newProduct);

        $basket->addBasketElement($basketElement);

        static::assertSame(
            '30.000',
            $basket->getTotal(false, false),
            '::getTotal() for non-recurrent payments only'
        );

        $basket->removeElement($basketElement);

        // Delivery
        $delivery = new Delivery();
        $basket->setDeliveryMethod($delivery);

        static::assertSame(
            '150.000',
            $basket->getTotal(false),
            '::getTotal() - return 150'
        );
        static::assertSame(
            '179.400',
            $basket->getTotal(true),
            '::getTotal() w/o vat return 179.40'
        );
        static::assertSame(
            '29.400',
            $basket->getVatAmount(),
            '::getVatAmount() w/o vat return 29.4'
        );
    }

    public function testBasket(): void
    {
        $basket = $this->getPreparedBasket();

        $product = $this->getMockProduct();

        // check if the product is part of the basket
        static::assertFalse($basket->hasProduct($product), '::hasProduct() - The product is not present in the basket');

        $basketElement = new BasketElement();
        $basketElement->setProduct('product_code', $product);

        $basket->addBasketElement($basketElement);

        static::assertTrue($basket->hasProduct($product), '::hasProduct() - The product is not present in the basket');

        // Covering all of the isValid method
        static::assertTrue($basket->isValid(true), '::isValid() return true for element only');
        static::assertFalse($basket->isValid(), '::isValid() return false for the complete check because payment address is invalid');

        $invalidBasketElement = $this->createMock(BasketElementInterface::class);
        $invalidBasketElement->expects(static::any())
            ->method('isValid')
            ->willReturn(false);
        $invalidBasketElement->expects(static::any())
            ->method('getPosition')
            ->willReturn(1);
        $invalidBasketElement->expects(static::any())
            ->method('getProduct')
            ->willReturn($product);

        $basket->addBasketElement($invalidBasketElement);
        static::assertFalse($basket->isValid(true), '::isValid() return false if an element is invalid');

        $basket->setBasketElements([]);
        $basket->addBasketElement($basketElement);
        static::assertFalse($basket->isValid(), '::isValid() return false for the complete check because payment address is invalid');

        $basket->setBillingAddress($this->getMockAddress());
        static::assertFalse($basket->isValid(), '::isValid() return false for the complete check because payment method is invalid');

        $basket->setPaymentMethod($this->createMock(PaymentInterface::class));
        static::assertFalse($basket->isValid(), '::isValid() return false for the complete check because delivery method is invalid');

        $deliveryMethod = $this->createMock(ServiceDeliveryInterface::class);
        $deliveryMethod->expects(static::any())
            ->method('isAddressRequired')
            ->willReturn(false);
        $basket->setDeliveryMethod($deliveryMethod);
        static::assertTrue($basket->isValid(), '::isValid() return true for the complete check because delivery method doesn\'t require an address');

        $requiredDelivery = $this->createMock(ServiceDeliveryInterface::class);
        $requiredDelivery->expects(static::any())
            ->method('isAddressRequired')
            ->willReturn(true);
        $basket->setDeliveryMethod($requiredDelivery);
        static::assertFalse($basket->isValid(), '::isValid() return false for the complete check because delivery address is invalid');

        $basket->setDeliveryAddress($this->getMockAddress());
        static::assertTrue($basket->isValid(), '::isValid() return true for the complete check because everything is fine');

        static::assertTrue($basket->isAddable($product), '::isAddable() return true');
        static::assertFalse($basket->hasRecurrentPayment(), '::hasRecurrentPayment() return false');

        static::assertTrue($basket->hasProduct($product), '::hasProduct() return true');

        static::assertTrue($basket->hasBasketElements(), '::hasElement() return true ');
        static::assertSame(1, $basket->countBasketElements(), '::countElements() return 1');
        static::assertNotEmpty($basket->getBasketElements(), '::getElements() is not empty');

        static::assertInstanceOf(BasketElement::class, $element = $basket->getElement($product), '::getElement() - return a BasketElement');

        static::assertInstanceOf(BasketElement::class, $basket->removeElement($element), '::removeElement() - return the removed BasketElement');

        static::assertFalse($basket->hasBasketElements(), '::hasElement() return false');
        static::assertSame(0, $basket->countBasketElements(), '::countElements() return 0');
        static::assertEmpty($basket->getBasketElements(), '::getElements() is empty');

        $basket->reset();
        static::assertFalse($basket->isValid(), '::isValid() return false after reset');
    }

    public function testSerialize(): void
    {
        $product = $this->getMockProduct();

        $basketElement = new BasketElement();
        $basketElement->setProduct('product_code', $product);

        $provider = $this->createMock(ProductProviderInterface::class);
        $manager = $this->createMock(ProductManagerInterface::class);
        $manager->expects(static::any())->method('getClass')->willReturn('BasketTest_Product');

        $definition = new ProductDefinition($provider, $manager);

        $pool = new Pool();
        $pool->addProduct('product_code', $definition);

        $basket = new Basket();

        $basket->setProductPool($pool);

        $basket->addBasketElement($basketElement);

        $basket->setDeliveryAddress($this->getMockAddress());
        $basket->setBillingAddress($this->getMockAddress());
        $basket->setCustomer($this->createMock(CustomerInterface::class));

        $data = $basket->serialize();

        static::assertIsString($data);
        static::assertStringStartsWith('a:11:', $data, 'the serialized array has 11 elements');

        // Ensuring all needed keys are present
        $expectedKeys = [
            'basketElements',
            'positions',
            'deliveryMethodCode',
            'paymentMethodCode',
            'cptElement',
            'options',
            'locale',
            'currency',
            'deliveryAddress',
            'billingAddress',
            'customer',
        ];

        $basketData = unserialize($data);

        foreach ($expectedKeys as $key) {
            static::assertArrayHasKey($key, $basketData);
        }

        $basket->setDeliveryAddressId(1);
        $basket->setBillingAddressId(2);
        $basket->setCustomerId(3);

        $data = $basket->serialize();

        static::assertIsString($data);
        static::assertStringStartsWith('a:11:', $data, 'the serialized array has 11 elements');

        // Ensuring all needed keys are present
        $expectedKeys = [
            'basketElements',
            'positions',
            'deliveryMethodCode',
            'paymentMethodCode',
            'cptElement',
            'options',
            'locale',
            'currency',
            'deliveryAddressId',
            'billingAddressId',
            'customerId',
        ];

        $basketData = unserialize($data);

        foreach ($expectedKeys as $key) {
            static::assertArrayHasKey($key, $basketData);
        }

        $basket->reset();
        static::assertTrue(0 === \count($basket->getBasketElements()), '::reset() remove all elements');
        $basket->unserialize($data);
        static::assertTrue(1 === \count($basket->getBasketElements()), '::unserialize() restore elements');
    }

    public function testGetElementRaisesException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The product does not exist');

        $basket = new Basket();
        $basket->getElement(new Product());
    }

    public function testHasRecurrentPayment(): void
    {
        $basket = $this->getPreparedBasket();

        $product = $this->getMockProduct();
        $product->expects(static::once())
            ->method('isRecurrentPayment')
            ->willReturn(true);

        $basketElement = new BasketElement();
        $basketElement->setProduct('product_code', $product);

        $basket->addBasketElement($basketElement);

        static::assertTrue($basket->hasRecurrentPayment());
    }

    public function testHasProduct(): void
    {
        $basket = $this->getPreparedBasket();

        $product = $this->getMockProduct();

        static::assertFalse($basket->hasProduct($product), '::hasProduct false because basket is empty');

        $basketElement = $this->createMock(BasketElementInterface::class);
        $basketElement->expects(static::any())->method('getProduct')->willReturn($product);
        $basketElement->expects(static::any())->method('getPosition')->willReturn(1042);

        $basket->addBasketElement($basketElement);

        static::assertFalse($basket->hasProduct($product), '::hasProduct false because position invalid');

        $basketElement = new BasketElement();
        $basketElement->setProduct('product_code', $product);

        $basket->addBasketElement($basketElement);

        static::assertTrue($basket->hasProduct($product), '::hasProduct true');
    }

    public function testBuildPrices(): void
    {
        $basket = $this->getPreparedBasket();

        $basketElement = $this->createMock(BasketElementInterface::class);
        $basketElement->expects(static::any())->method('getProduct')->willReturn($this->getMockAddress());
        $basketElement->expects(static::any())->method('getPosition')->willReturn(0);

        $basket->addBasketElement($basketElement);

        $basket->buildPrices();

        static::assertCount(0, $basket->getBasketElements());
    }

    public function testClean(): void
    {
        $basket = $this->getPreparedBasket();

        $product = $this->getMockProduct();

        $basketElement = $this->createMock(BasketElementInterface::class);
        $basketElement->expects(static::any())->method('getProduct')->willReturn($product);
        $basketElement->expects(static::any())->method('getPosition')->willReturn(0);

        $deletedBasketElement = clone $basketElement;
        $deletedBasketElement->expects(static::any())->method('getDelete')->willReturn(true);

        $basket->addBasketElement($basketElement);
        $basket->addBasketElement($deletedBasketElement);

        $basket->clean();

        static::assertCount(1, $basket->getBasketElements());
    }

    public function testGettersSetters(): void
    {
        $basket = $this->getPreparedBasket();

        $product = $this->getMockProduct();

        $basketElement = new BasketElement();
        $basketElement->setProduct('product_code', $product);

        $basket->setBasketElements([$basketElement]);
        static::assertSame([$basketElement], $basket->getBasketElements());

        $basket->setDeliveryAddressId(1);
        static::assertSame(1, $basket->getDeliveryAddressId());

        $basket->setBillingAddressId(1);
        static::assertSame(1, $basket->getBillingAddressId());

        $deliveryMethod = $this->createMock(ServiceDeliveryInterface::class);
        $deliveryMethod->expects(static::any())
            ->method('getCode')
            ->willReturn(1);
        $basket->setDeliveryMethod($deliveryMethod);
        static::assertSame(1, $basket->getDeliveryMethodCode());

        $paymentMethod = $this->createMock(PaymentInterface::class);
        $paymentMethod->expects(static::any())
            ->method('getCode')
            ->willReturn(1);
        $basket->setPaymentMethod($paymentMethod);
        static::assertSame(1, $basket->getPaymentMethodCode());

        $basket->setCustomerId(1);
        static::assertSame(1, $basket->getCustomerId());

        $options = ['option1' => 'value1', 'option2' => 'value2'];
        $basket->setOptions($options);
        static::assertNull($basket->getOption('unexisting_option'));
        static::assertSame(42, $basket->getOption('unexisting_option', 42));
        static::assertSame('value1', $basket->getOption('option1'));
        static::assertSame($options, $basket->getOptions());

        $basket->setOption('option3', 'value3');
        static::assertSame('value3', $basket->getOption('option3'));

        $basket->setLocale('en');
        static::assertSame('en', $basket->getLocale());
    }

    protected function getPreparedBasket()
    {
        $basket = new Basket();

        // create the provider mock
        $provider = $this->createMock(ProductProviderInterface::class);

        $provider->expects(static::any())
            ->method('calculatePrice')
            ->willReturn(15);

        $provider->expects(static::any())
            ->method('isAddableToBasket')
            ->willReturn(true);

        // create the product manager mock
        $manager = $this->createMock(ProductManagerInterface::class);
        $manager->expects(static::any())->method('getClass')->willReturn('BasketTest_Product');

        $definition = new ProductDefinition($provider, $manager);

        $pool = new Pool();
        $pool->addProduct('product_code', $definition);

        $basket->setProductPool($pool);

        return $basket;
    }
}
