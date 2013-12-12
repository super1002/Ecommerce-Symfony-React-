<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sonata\InvoiceBundle\Entity;

use Sonata\Component\Invoice\InvoiceElementManagerInterface;
use Sonata\CoreBundle\Entity\DoctrineBaseManager;

class InvoiceElementManager extends DoctrineBaseManager implements InvoiceElementManagerInterface
{
}
