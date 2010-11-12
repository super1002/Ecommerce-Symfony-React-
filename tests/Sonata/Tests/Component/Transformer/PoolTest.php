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


class Poolest extends \PHPUnit_Framework_TestCase
{
    public function testPool()
    {
        $pool = new Pool;

        $transformer = new BasketTransformer;
        $pool->addTransformer('basket', $transformer);


        $transformer = new OrderTransformer;
        $pool->addTransformer('order', $transformer);

        $this->assertEquals(2, count($pool->getTransformers()), 'Pool return 2 elements');
        $this->assertInstanceOf('Sonata\\Component\\Transformer\\BasketTransformer', $pool->getTransformer('basket'), 'Pool return an FreeDelivery Instance');
    }
}