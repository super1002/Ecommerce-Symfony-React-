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

namespace Sonata\CustomerBundle\Tests\Block;

use PHPUnit\Framework\TestCase;
use Sonata\Component\Customer\CustomerManagerInterface;
use Sonata\CustomerBundle\Block\RecentCustomersBlockService;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Twig\Environment;

/**
 * @author Xavier Coureau <xcoureau@ekino.com>
 */
class RecentCustomersBlockServiceTest extends TestCase
{
    public function testGetName(): void
    {
        $environment = $this->createMock(Environment::class);

        $engineInterfaceMock = $this->createMock(EngineInterface::class);
        $customerManagerInterfaceMock = $this->createMock(CustomerManagerInterface::class);
        $block = new RecentCustomersBlockService($environment, $engineInterfaceMock, $customerManagerInterfaceMock);

        static::assertSame('Recent Customers', $block->getName());
    }
}
