<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Component\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Sonata\Component\Payment\Pool as PaymentPool;

/**
 * Transform a method code into a method instance
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class PaymentMethodTransformer implements DataTransformerInterface
{
    protected $paymentPool;

    public function __construct(PaymentPool $paymentPool)
    {
        $this->paymentPool = $paymentPool;
    }

    public function reverseTransform($value)
    {
        return $this->paymentPool->getMethod($value);
    }

    public function transform($value)
    {
        return $value ? $value->getCode() : null;
    }
}
