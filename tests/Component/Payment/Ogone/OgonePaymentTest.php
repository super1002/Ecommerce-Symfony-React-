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

namespace Sonata\Component\Tests\Payment\Ogone;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sonata\Component\Basket\Basket;
use Sonata\Component\Currency\Currency;
use Sonata\Component\Customer\CustomerInterface;
use Sonata\Component\Order\OrderInterface;
use Sonata\Component\Payment\Ogone\OgonePayment;
use Sonata\Component\Payment\TransactionInterface;
use Sonata\Component\Product\ProductInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class OgonePaymentTest extends TestCase
{
    public function testValidPayment(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $router->expects(static::once())->method('generate')->willReturn('http://www.google.com');

        $payment = new OgonePayment($router, $logger, $templating, true);
        $payment->setCode('ogone_1');
        $payment->setOptions([
            'url_return_ok' => 'sonata_payment_confirmation',
            'url_return_ko' => '',
            'url_callback' => '',
            'template' => '',
            'form_url' => '',
            'sha_key' => '',
            'sha-out_key' => '',
            'pspid' => '',
            'home_url' => '',
            'catalog_url' => '',
        ]);

        $basket = $this->createMock(Basket::class);
        $product = $this->createMock(ProductInterface::class);

        $date = new \DateTime('1981-11-30', new \DateTimeZone('Europe/Paris'));

        $order = new OgonePaymentTest_Order();
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

        static::assertSame('ogone_1', $payment->getCode(), 'Ogone Payment return the correct code');
        static::assertTrue($payment->isAddableProduct($basket, $product));
        static::assertTrue($payment->isBasketValid($basket));
        static::assertTrue($payment->isRequestValid($transaction));

        static::assertTrue($payment->isCallbackValid($transaction));

        static::assertInstanceOf(Response::class, $payment->handleError($transaction));
        static::assertInstanceOf(Response::class, $payment->sendConfirmationReceipt($transaction));
    }

    public function testValidSendbankPayment(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $templating->expects(static::once())->method('renderResponse')->willReturnCallback([$this, 'callbackValidsendbank']);

        $router = $this->createMock(RouterInterface::class);

        $date = new \DateTime('1981-11-30', new \DateTimeZone('Europe/Paris'));

        $customer = $this->createMock(CustomerInterface::class);

        $order = new OgonePaymentTest_Order();
        $order->setCreatedAt($date);
        $order->setId(2);
        $order->setReference('FR');

        $currency = new Currency();
        $currency->setLabel('EUR');
        $order->setCurrency($currency);
        $order->setCustomer($customer);
        $order->setLocale('es');

        $payment = new OgonePayment($router, $logger, $templating, true);
        $payment->setCode('ogone_1');
        $payment->setOptions([
            'url_return_ok' => '',
            'url_return_ko' => '',
            'url_callback' => '',
            'template' => '',
            'form_url' => '',
            'sha_key' => '',
            'sha-out_key' => '',
            'pspid' => '',
            'home_url' => '',
            'catalog_url' => '',
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

        $payment = new OgonePayment($router, $logger, $templating, true);
        $payment->setCode('ogone_1');
        $payment->setOptions([
                'url_return_ok' => '',
                'url_return_ko' => '',
                'url_callback' => '',
                'template' => '',
                'form_url' => '',
                'sha_key' => '',
                'sha-out_key' => '',
                'pspid' => '',
                'home_url' => '',
                'catalog_url' => '',
        ]);

        static::assertSame($expected, $payment->encodeString($data));
    }

    public static function getEncodeStringValues()
    {
        return [
            ['valid', 'valid'],
            ['!@#$', '!@#$'],
            ['foo=bar', 'foo=bar'],
        ];
    }

    public function callbackValidsendbank($template, $params)
    {
        if (!$params['shasign']) {
            throw new \RuntimeException('Ogone validation should be ok');
        }

        if ('FR' !== $params['fields']['orderId']) {
            throw new \RuntimeException('Invalid ogone orderId');
        }

        return new Response();
    }

    public static function getCallback($name)
    {
        $params = [
                'orderID' => 'FR',
                'currency' => null,
                'amount' => 'amount',
                'PM' => 'PM',
                'ACCEPTANCE' => 'ACCEPTANCE',
                'STATUS' => 'STATUS',
                'CARDNO' => 'CARDNO',
                'ED' => 'ED',
                'CN' => 'CN',
                'TRXDATE' => 'TRXDATE',
                'PAYID' => 'PAYID',
                'NCERROR' => 'NCERROR',
                'BRAND' => 'BRAND',
                'IP' => 'IP',
        ];

        if (0 === strcasecmp('shasign', $name)) {
            uksort($params, 'strcasecmp');

            $shaKey = '';

            $shasignStr = '';
            foreach ($params as $key => $param) {
                if (null !== $param && '' !== $param) {
                    $shasignStr .= strtoupper($key).'='.$param.$shaKey;
                }
            }

            return strtoupper(sha1($shasignStr));
        }

        $params['check'] = '0d2ccfb54a1ffec609919fa4fbf8603614019997';

        return $params[$name];
    }

    public function testIsCallbackValid(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $router = $this->createMock(RouterInterface::class);

        $payment = new OgonePayment($router, $logger, $templating, true);

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

        $payment = new OgonePayment($router, $logger, $templating, true);

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(static::once())->method('get')->willReturn('reference');

        static::assertSame('reference', $payment->getOrderReference($transaction));
    }

    public function testApplyTransactionId(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $templating = $this->createMock(EngineInterface::class);
        $router = $this->createMock(RouterInterface::class);

        $payment = new OgonePayment($router, $logger, $templating, true);

        $transaction = $this->createMock(TransactionInterface::class);
        $transaction->expects(static::once())->method('setTransactionId');

        $payment->applyTransactionId($transaction);
    }
}
