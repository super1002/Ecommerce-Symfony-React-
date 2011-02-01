<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Tests\Component\Transformer;

use Sonata\Component\Transformer\Pool;
use Sonata\Component\Transformer\BasketTransformer;
use Sonata\Component\Transformer\OrderTransformer;


class BasketTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * useless test ....
     *
     * @return void
     */
    public function testOrder()
    {

        $logger = $this->getMock('Logger', array('emerg'));
        $logger
            ->expects($this->any())
            ->method('emerg');

        // Mock the user
        $user = $this->getMock('User', array('getId', 'getUsername'));
        $user
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(42));

        $user
            ->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue('rande'));

        $products = array();

        $products[] = new \Sonata\Tests\Component\Basket\Product;
        $products[] = new \Sonata\Tests\Component\Basket\Product;


        // Mock the product repository
        $repository = $this->getMock('ProductRepository', array('createOrderElement'));

        $repository->expects($this->exactly(2))
            ->method('createOrderElement')
            ->will($this->onConsecutiveCalls($this->getMock('OrderElement'), $this->getMock('OrderElement')));

        $product_pool = new  \Sonata\Component\Product\Pool;
        $product_pool->addProduct(array(
            'id'            => 'test',
            'class'         => 'Sonata\\Tests\\Component\\Basket\\Product',
        ));

        $entity_manager = $this->getMock('EntityManager', array('getRepository'));
        $entity_manager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $product_pool->setEntityManager($entity_manager);

        $transformer = new BasketTransformer;
        $transformer->setProductPool($product_pool);
        $transformer->setLogger($logger);

        try {
            $transformer->transformIntoOrder(null, null);
            $this->fail('::transformIntoOrder() should raise an error if the user is null');
        } catch (\RuntimeException $e) {
            // ok ? no pass method in PHPUnit ?
            $this->assertEquals('Invalid user', $e->getMessage());
        }
        
        try {
            $transformer->transformIntoOrder($user, null);
            $this->fail('::transformIntoOrder() should raise an error if the basket is null');
        } catch (\RuntimeException $e) {
            // ok ? no pass method in PHPUnit ?
            $this->assertEquals('Invalid basket', $e->getMessage());
        }

        $billingAddress = new \Sonata\Tests\Component\Basket\Address;
        $shipping_address = new \Sonata\Tests\Component\Basket\Address;
        $deliveryMethod = new \Sonata\Tests\Component\Basket\Delivery;

        $basketElements = array();

        $basketElement = $this->getMock('Sonata\\Component\\Basket\\BasketElement');
        $basketElement->expects($this->any())
            ->method('getProduct')
            ->will($this->returnValue($products[0]));


        $basketElements[] = $basketElement;

        $basketElement = $this->getMock('Sonata\\Component\\Basket\\BasketElement');
        $basketElement->expects($this->any())
            ->method('getProduct')
            ->will($this->returnValue($products[0]));

        $basketElements[] = $basketElement;

        $basket = $this->getMock('Basket', array('getDeliveryPrice','getTotal', 'getBillingAddress', 'getDeliveryMethod', 'getShippingAddress', 'getElements'));
        
        $basket->expects($this->once())
            ->method('getBillingAddress')
            ->will($this->returnValue($billingAddress));

        $basket->expects($this->once())
            ->method('getShippingAddress')
            ->will($this->returnValue($shipping_address));

        $basket->expects($this->exactly(2))
            ->method('getDeliveryMethod')
            ->will($this->returnValue($deliveryMethod));

        $basket->expects($this->exactly(2))
            ->method('getTotal')
            ->will($this->onConsecutiveCalls(12, 14.78));

        $basket->expects($this->exactly(1))
            ->method('getDeliveryPrice')
            ->will($this->returnValue(2));

        $basket->expects($this->once())
            ->method('getElements')
            ->will($this->returnValue($basketElements));

        $order = $this->getMock('Sonata\\Tests\\Component\\Basket\\Order');

        $order->expects($this->exactly(2))
            ->method('addOrderElement');

        $transformer->setOptions(array('class_order' => get_class($order), 'order_instance' => $order));

        $order = $transformer->transformIntoOrder($user, $basket);
    }
}