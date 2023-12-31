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

use PHPUnit\Framework\TestCase;
use Sonata\Component\Basket\BasketBuilder;
use Sonata\Component\Basket\BasketElementInterface;
use Sonata\Component\Basket\BasketInterface;
use Sonata\Component\Customer\AddressInterface;
use Sonata\Component\Customer\AddressManagerInterface;
use Sonata\Component\Delivery\Pool as DeliveryPool;
use Sonata\Component\Payment\Pool as PaymentPool;
use Sonata\Component\Product\Pool as ProductPool;
use Sonata\Component\Product\ProductDefinition;
use Sonata\Component\Product\ProductManagerInterface;
use Sonata\Component\Product\ProductProviderInterface;

class BasketBuilderTest extends TestCase
{
    public function testBuildWithInvalidProductCode(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The product code is empty');

        $productPool = new ProductPool();

        $deliveryPool = new DeliveryPool();

        $paymentPool = new PaymentPool();

        $addressManager = $this->createMock(AddressManagerInterface::class);

        $basketBuilder = new BasketBuilder($productPool, $addressManager, $deliveryPool, $paymentPool);

        $basketElement = $this->createMock(BasketElementInterface::class);

        $basketElements = [$basketElement];

        $basket = $this->createMock(BasketInterface::class);
        $basket->expects(static::once())->method('getBasketElements')->willReturn($basketElements);

        $basketBuilder->build($basket);
    }

    public function testBuildWithNonExistentProductCode(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The product definition `non_existent_product_code` does not exist!');

        $productPool = new ProductPool();

        $deliveryPool = new DeliveryPool();

        $paymentPool = new PaymentPool();

        $addressManager = $this->createMock(AddressManagerInterface::class);

        $basketBuilder = new BasketBuilder($productPool, $addressManager, $deliveryPool, $paymentPool);

        $basketElement = $this->createMock(BasketElementInterface::class);
        $basketElement->expects(static::exactly(2))->method('getProductCode')->willReturn('non_existent_product_code');

        $basketElements = [$basketElement];

        $basket = $this->createMock(BasketInterface::class);
        $basket->expects(static::once())->method('getBasketElements')->willReturn($basketElements);

        $basketBuilder->build($basket);
    }

    public function testBuild(): void
    {
        $productProvider = $this->createMock(ProductProviderInterface::class);
        $productManager = $this->createMock(ProductManagerInterface::class);

        $definition = new ProductDefinition($productProvider, $productManager);

        $productPool = new ProductPool();
        $productPool->addProduct('test', $definition);
        $deliveryPool = new DeliveryPool();

        $paymentPool = new PaymentPool();

        $address = $this->createMock(AddressInterface::class);
        $addressManager = $this->createMock(AddressManagerInterface::class);
        $addressManager->expects(static::exactly(2))->method('findOneBy')->willReturn($address);
        $basketBuilder = new BasketBuilder($productPool, $addressManager, $deliveryPool, $paymentPool);

        $basketElement = $this->createMock(BasketElementInterface::class);
        $basketElement->expects(static::exactly(2))->method('getProductCode')->willReturn('test');
        $basketElement->expects(static::once())->method('setProductDefinition');

        $basketElements = [$basketElement];

        $basket = $this->createMock(BasketInterface::class);
        $basket->expects(static::once())->method('getBasketElements')->willReturn($basketElements);

        $basket->expects(static::once())->method('getDeliveryAddressId')->willReturn(1);
        $basket->expects(static::once())->method('getDeliveryMethodCode')->willReturn('ups');
        $basket->expects(static::once())->method('getBillingAddressId')->willReturn(2);
        $basket->expects(static::once())->method('getPaymentMethodCode')->willReturn('credit_cart');

        $basket->expects(static::once())->method('setDeliveryAddress');
        $basket->expects(static::once())->method('setDeliveryMethod');
        $basket->expects(static::once())->method('setBillingAddress');
        $basket->expects(static::once())->method('setPaymentMethod');

        $basket->expects(static::once())->method('buildPrices');

        $basketBuilder->build($basket);
    }
}
