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

namespace Sonata\Component\Tests\Payment;

use Buzz\Browser;
use Buzz\Client\ClientInterface;
use PHPUnit\Framework\TestCase;
use Sonata\Component\Payment\Debug\DebugPayment;
use Sonata\Component\Payment\TransactionInterface;
use Sonata\Component\Payment\TransactionManagerInterface;
use Symfony\Component\Routing\RouterInterface;

class DebugPaymentTest extends TestCase
{
    public function testDebugPayment(): void
    {
        $payment = $this->getDebugPayment();

        $order = $this->getOrder();

        $transaction = $this->getTransactionManager()->create();
        $transaction->setPaymentCode($payment->getCode());
        $transaction->setOrder($order);

        /*
         * Payment refused
         */
        $transaction->setParameters(['action' => 'refuse']);
        $payment->sendConfirmationReceipt($transaction);

        static::assertSame(TransactionInterface::STATE_KO, $transaction->getState());
        static::assertSame(TransactionInterface::STATUS_ERROR_VALIDATION, $transaction->getStatusCode());

        /*
         * Payment accepted
         */
        $transaction->setParameters(['action' => 'accept']);
        $payment->sendConfirmationReceipt($transaction);

        static::assertSame(TransactionInterface::STATE_OK, $transaction->getState());
        static::assertSame(TransactionInterface::STATUS_VALIDATED, $transaction->getStatusCode());
    }

    /**
     * @return \Sonata\Component\Order\OrderInterface
     */
    public function getOrder()
    {
        $date = new \DateTime('1981-11-30', new \DateTimeZone('Europe/Paris'));

        $order = new DebugPaymentTest_Order();
        $order->setCreatedAt($date);

        return $order;
    }

    /**
     * @return \Sonata\Component\Payment\TransactionManagerInterface
     */
    protected function getTransactionManager()
    {
        $transactionManager = $this->createMock(TransactionManagerInterface::class);

        $transactionManager->expects(static::once())
            ->method('create')
            ->willReturn(new DebugPaymentTest_Transaction());

        return $transactionManager;
    }

    /**
     * @return \Sonata\Component\Payment\Debug\DebugPayment
     */
    protected function getDebugPayment()
    {
        $router = $this->createMock(RouterInterface::class);

        $client = $this->createMock(ClientInterface::class);

        $browser = new Browser($client);

        $payment = new DebugPayment($router, $browser);

        return $payment;
    }
}
