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

namespace Sonata\Component\Tests\Payment\Scellius;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sonata\Component\Basket\Basket;
use Sonata\Component\Currency\Currency;
use Sonata\Component\Customer\CustomerInterface;
use Sonata\Component\Order\OrderInterface;
use Sonata\Component\Payment\Scellius\ScelliusPayment;
use Sonata\Component\Payment\Scellius\ScelliusTransactionGeneratorInterface;
use Sonata\Component\Payment\TransactionInterface;
use Sonata\Component\Product\ProductInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class ScelliusPaymentTest extends TestCase
{
    /**
     * useless test ....
     */
    public function testValidPayment(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $generator = $this->createMock(ScelliusTransactionGeneratorInterface::class);

        $payment = new ScelliusPayment($router, $logger, $templating, $generator, true);
        $payment->setCode('free_1');
        $payment->setOptions([
            'base_folder' => __DIR__,
            'response_command' => 'cat response_ok.txt && echo ',
        ]);

        $basket = $this->createMock(Basket::class);
        $product = $this->createMock(ProductInterface::class);

        $date = new \DateTime('1981-11-30', new \DateTimeZone('Europe/Paris'));

        $order = new ScelliusPaymentTest_Order();
        $order->setCreatedAt($date);
        $order->setId(2);
        $order->setReference('FR');
        $order->setLocale('es');

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(static::any())->method('get')->willReturnCallback([$this, 'getCallback']);
        //        $transaction->expects($this->once())->method('setTransactionId');
        $transaction->expects(static::any())->method('getOrder')->willReturn($order);
        $transaction->expects(static::any())->method('getCreatedAt')->willReturn($date);
        $transaction->expects(static::any())->method('getInformation')->willReturn('');

        static::assertSame('free_1', $payment->getCode(), 'Pass Payment return the correct code');
        static::assertTrue($payment->isAddableProduct($basket, $product));
        static::assertTrue($payment->isBasketValid($basket));
        static::assertTrue($payment->isRequestValid($transaction));

        static::assertTrue($payment->isCallbackValid($transaction));

        static::assertInstanceOf(Response::class, $payment->handleError($transaction));
        static::assertInstanceOf(Response::class, $payment->sendConfirmationReceipt($transaction));

        //        $response = $payment->sendbank($order);
//
//        $this->assertTrue($response->headers->has('Location'));
//        $this->assertEquals('http://foo.bar/ok-url', $response->headers->get('Location'));
//        $this->assertFalse($response->isCacheable());
//
//        $this->assertEquals($payment->getOrderReference($transaction), '0001231');
//
//        $payment->applyTransactionId($transaction);
    }

    public function testSendConfirmationReceipt(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $generator = $this->createMock(ScelliusTransactionGeneratorInterface::class);

        $payment = new ScelliusPayment($router, $logger, $templating, $generator, true);
        $payment->setOptions([
            'base_folder' => __DIR__,
            'response_command' => 'cat response_ko.txt && echo ',
        ]);

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(static::any())->method('get')->willReturn('" >> /dev/null');
        $transaction->expects(static::any())->method('getParameters')->willReturn([]);
        $transaction->expects(static::any())->method('getOrder')->willReturn(
            $this->createMock(OrderInterface::class)
        );

        static::assertFalse($payment->sendConfirmationReceipt($transaction));

        $payment->setOptions([
            'base_folder' => __DIR__,
            'response_command' => 'cat response_nok.txt && echo ',
        ]);

        static::assertFalse($payment->sendConfirmationReceipt($transaction));

        $payment->setOptions([
            'base_folder' => __DIR__,
            'response_command' => 'cat response_code_nok.txt && echo ',
        ]);

        static::assertFalse($payment->sendConfirmationReceipt($transaction));

        $payment->setOptions([
            'base_folder' => __DIR__,
            'response_command' => 'cat response_ok.txt && echo ',
        ]);

        static::assertInstanceOf(Response::class, $payment->sendConfirmationReceipt($transaction));
    }

    public function testIsCallbackValid(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $generator = $this->createMock(ScelliusTransactionGeneratorInterface::class);

        $payment = new ScelliusPayment($router, $logger, $templating, $generator, true);

        $order = $this->createMock(OrderInterface::class);
        $order->expects(static::any())->method('getCreatedAt')->willReturn(new \DateTime());

        $check = sha1(
            $order->getReference().
            $order->getCreatedAt()->format('m/d/Y:G:i:s').
            $order->getId()
        );

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(static::once())->method('getOrder')->willReturn(null);

        static::assertFalse($payment->isCallbackValid($transaction));

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(static::exactly(2))->method('getOrder')->willReturn($order);
        $transaction->expects(static::once())->method('get')->willReturn($check);

        static::assertTrue($payment->isCallbackValid($transaction));

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(static::exactly(2))->method('getOrder')->willReturn($order);
        $transaction->expects(static::once())->method('get')->willReturn('untest');
        $transaction->expects(static::once())->method('setState');
        $transaction->expects(static::once())->method('setStatusCode');
        $transaction->expects(static::once())->method('addInformation');

        static::assertFalse($payment->isCallbackValid($transaction));
    }

    public function testGetOrderReference(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $generator = $this->createMock(ScelliusTransactionGeneratorInterface::class);

        $payment = new ScelliusPayment($router, $logger, $templating, $generator, true);

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(static::once())->method('get')->willReturn('reference');

        static::assertSame('reference', $payment->getOrderReference($transaction));
    }

    public function testApplyTransactionId(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $generator = $this->createMock(ScelliusTransactionGeneratorInterface::class);

        $payment = new ScelliusPayment($router, $logger, $templating, $generator, true);

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(static::once())->method('setTransactionId');

        $payment->applyTransactionId($transaction);
    }

    public function testInvalidCurrencySendbankPayment(): void
    {
        $this->expectException(\RuntimeException::class);

        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $router = $this->createMock(RouterInterface::class);

        $generator = $this->createMock(ScelliusTransactionGeneratorInterface::class);

        $date = new \DateTime('1981-11-30', new \DateTimeZone('Europe/Paris'));

        $order = new ScelliusPaymentTest_Order();
        $order->setCreatedAt($date);
        $order->setId(2);
        $order->setReference('FR');
        $order->setLocale('es');

        $payment = new ScelliusPayment($router, $logger, $templating, $generator, true);
        $payment->setCode('free_1');
        $payment->setOptions([
            'base_folder' => __DIR__,
            'request_command' => 'cat request_ok.txt && echo ',
        ]);

        $payment->sendbank($order);
    }

    public function testValidSendbankPayment(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $templating->expects(static::once())->method('renderResponse')->willReturnCallback([$this, 'callbackValidsendbank']);
        $generator = $this->createMock(ScelliusTransactionGeneratorInterface::class);

        $router = $this->createMock(RouterInterface::class);

        $date = new \DateTime('1981-11-30', new \DateTimeZone('Europe/Paris'));

        $customer = $this->createMock(CustomerInterface::class);
        $customer->expects(static::once())->method('getId')->willReturn(42);
        $customer->expects(static::once())->method('getEmail')->willReturn('contact@sonata-project.org');

        $order = new ScelliusPaymentTest_Order();
        $order->setCreatedAt($date);
        $order->setId(2);
        $order->setReference('FR');

        $currency = new Currency();
        $currency->setLabel('EUR');
        $order->setCurrency($currency);
        $order->setCustomer($customer);
        $order->setLocale('es');

        $payment = new ScelliusPayment($router, $logger, $templating, $generator, true);
        $payment->setCode('free_1');
        $payment->setOptions([
            'base_folder' => __DIR__,
            'request_command' => 'cat request_ok.txt && echo ',
        ]);

        $response = $payment->sendbank($order);

        static::assertInstanceOf(Response::class, $response);
    }

    /**
     * @dataProvider getEncodeStringValues
     */
    public function testEncodeString($data, $expected): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $generator = $this->createMock(ScelliusTransactionGeneratorInterface::class);

        $payment = new ScelliusPayment($router, $logger, $templating, $generator, true);

        static::assertSame($expected, $payment->encodeString($data));
    }

    public static function getEncodeStringValues()
    {
        return [
            ['valid', 'valid'],
            ['!@#$', '!@\#\$'],
            ['foo=bar', 'foo=bar'],
        ];
    }

    public function callbackValidSendbank($template, $params)
    {
        if (!$params['scellius']['valid']) {
            throw new \RuntimeException('Scellius validation should be ok');
        }

        if ('<div>message</div>' !== $params['scellius']['content']) {
            throw new \RuntimeException('Invalid scellius html message');
        }

        return new Response();
    }

    public static function getCallback($name)
    {
        if ('reference' === $name) {
            return '0001231';
        }

        if ('transaction_id' === $name) {
            return 1;
        }

        if ('check' === $name) {
            return '0d2ccfb54a1ffec609919fa4fbf8603614019997';
        }
    }
}
