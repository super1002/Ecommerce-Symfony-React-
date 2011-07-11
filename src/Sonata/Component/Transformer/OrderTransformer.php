<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Component\Transformer;

use Sonata\Component\Customer\CustomerInterface;
use Sonata\Component\Order\OrderInterface;
use Sonata\Component\Basket\BasketInterface;

class OrderTransformer extends BaseTransformer
{
    public function transformIntoBasket(CustomerInterface $customer, OrderInterface $order, BasketInterface $basket)
    {
        // we reset the current basket
        $basket->reset();

        // We are free to convert !
        foreach ($order->getOrderElements() as $orderElement) {
            $repository = $this->getProductPool()->getRepository($orderElement->getProductType());
            $product    = $repository->find($orderElement->getProductId());

            if (!$product) {
                continue;
            }

            $repository->basketAddProduct($basket, $product, $orderElement);
        }

        $basket->buildPrices();

        return $basket;
    }
}