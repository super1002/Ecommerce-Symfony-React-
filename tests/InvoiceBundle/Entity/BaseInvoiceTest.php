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

namespace Sonata\InvoiceBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Sonata\BasketBundle\Entity\BaseBasketElement;
use Sonata\InvoiceBundle\Entity\BaseInvoice;
use Sonata\InvoiceBundle\Entity\BaseInvoiceElement;
use Sonata\ProductBundle\Entity\BaseProduct;

class InvoiceTest extends BaseInvoice
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class BaseInvoiceTest extends TestCase
{
    public function testInvoiceElementsVatAmounts(): void
    {
        $invoice = new InvoiceTest();

        $element1 = $this->getMockBuilder(BaseInvoiceElement::class)->getMock();
        $element1->expects(static::any())->method('getVatRate')->willReturn(20);
        $element1->expects(static::any())->method('getVatAmount')->willReturn(3);

        $element2 = $this->getMockBuilder(BaseInvoiceElement::class)->getMock();
        $element2->expects(static::any())->method('getVatRate')->willReturn(10);
        $element2->expects(static::any())->method('getVatAmount')->willReturn(2);

        $element3 = $this->getMockBuilder(BaseBasketElement::class)->getMock();
        $element3->expects(static::any())->method('getProduct')->willReturn(
            $this->getMockBuilder(BaseProduct::class)->getMock()
        );
        $element3->expects(static::any())->method('getVatRate')->willReturn(10);
        $element3->expects(static::any())->method('getVatAmount')->willReturn(5);

        $invoice->setInvoiceElements([$element1, $element2, $element3]);

        $items = $invoice->getVatAmounts();

        static::assertIsArray($items, 'Should return an array');

        foreach ($items as $item) {
            static::assertArrayHasKey('rate', $item, 'Array items should contains a "rate" key');
            static::assertArrayHasKey('amount', $item, 'Array items should contains a "amount" key');

            static::assertContains($item['rate'], [10, 20]);
            static::assertContains($item['amount'], ['7.000', '3']);
        }
    }
}
