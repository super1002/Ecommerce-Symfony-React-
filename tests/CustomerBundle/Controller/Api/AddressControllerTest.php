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

namespace Sonata\CustomerBundle\Tests\Controller\Api;

use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use PHPUnit\Framework\TestCase;
use Sonata\Component\Customer\AddressInterface;
use Sonata\Component\Customer\AddressManagerInterface;
use Sonata\CustomerBundle\Controller\Api\AddressController;
use Sonata\DatagridBundle\Pager\PagerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class AddressControllerTest extends TestCase
{
    public function testGetAddressesAction(): void
    {
        $pager = $this->createStub(PagerInterface::class);
        $addressManager = $this->createMock(AddressManagerInterface::class);
        $addressManager->expects(static::once())->method('getPager')->willReturn($pager);

        $paramFetcher = $this->createMock(ParamFetcherInterface::class);
        $paramFetcher->expects(static::exactly(3))->method('get')->willReturn(1, 10, null);
        $paramFetcher->expects(static::once())->method('all')->willReturn([]);

        static::assertSame($pager, $this->createAddressController(null, $addressManager)->getAddressesAction($paramFetcher));
    }

    public function testGetAddressAction(): void
    {
        $address = $this->createMock(AddressInterface::class);

        static::assertSame($address, $this->createAddressController($address)->getAddressAction(1));
    }

    public function testGetAddressActionNotFoundException(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Address (42) not found');

        $this->createAddressController()->getAddressAction(42);
    }

    public function testPostAddressAction(): void
    {
        $address = $this->createMock(AddressInterface::class);

        $addressManager = $this->createMock(AddressManagerInterface::class);
        $addressManager->expects(static::once())->method('save')->willReturn($address);

        $form = $this->createMock(Form::class);
        $form->expects(static::once())->method('handleRequest');
        $form->expects(static::once())->method('isSubmitted')->willReturn(true);
        $form->expects(static::once())->method('isValid')->willReturn(true);
        $form->expects(static::once())->method('getData')->willReturn($address);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(static::once())->method('createNamed')->willReturn($form);

        $view = $this->createAddressController(null, $addressManager, $formFactory)->postAddressAction(new Request());

        static::assertInstanceOf(View::class, $view);
    }

    public function testPostCustomerInvalidAction(): void
    {
        $address = $this->createMock(AddressInterface::class);

        $addressManager = $this->createMock(AddressManagerInterface::class);
        $addressManager->expects(static::never())->method('save')->willReturn($address);

        $form = $this->createMock(Form::class);
        $form->expects(static::once())->method('handleRequest');
        $form->expects(static::once())->method('isSubmitted')->willReturn(true);
        $form->expects(static::once())->method('isValid')->willReturn(false);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(static::once())->method('createNamed')->willReturn($form);

        $view = $this->createAddressController(null, $addressManager, $formFactory)->postAddressAction(new Request());

        static::assertInstanceOf(FormInterface::class, $view);
    }

    public function testPutAddressAction(): void
    {
        $address = $this->createMock(AddressInterface::class);

        $addressManager = $this->createMock(AddressManagerInterface::class);
        $addressManager->expects(static::once())->method('findOneBy')->willReturn($address);
        $addressManager->expects(static::once())->method('save')->willReturn($address);

        $form = $this->createMock(Form::class);
        $form->expects(static::once())->method('handleRequest');
        $form->expects(static::once())->method('isSubmitted')->willReturn(true);
        $form->expects(static::once())->method('isValid')->willReturn(true);
        $form->expects(static::once())->method('getData')->willReturn($address);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(static::once())->method('createNamed')->willReturn($form);

        $view = $this->createAddressController($address, $addressManager, $formFactory)->putAddressAction(1, new Request());

        static::assertInstanceOf(View::class, $view);
    }

    public function testPutAddressInvalidAction(): void
    {
        $address = $this->createMock(AddressInterface::class);

        $addressManager = $this->createMock(AddressManagerInterface::class);
        $addressManager->expects(static::once())->method('findOneBy')->willReturn($address);
        $addressManager->expects(static::never())->method('save')->willReturn($address);

        $form = $this->createMock(Form::class);
        $form->expects(static::once())->method('handleRequest');
        $form->expects(static::once())->method('isSubmitted')->willReturn(true);
        $form->expects(static::once())->method('isValid')->willReturn(false);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(static::once())->method('createNamed')->willReturn($form);

        $view = $this->createAddressController($address, $addressManager, $formFactory)->putAddressAction(1, new Request());

        static::assertInstanceOf(FormInterface::class, $view);
    }

    public function testDeleteAddressAction(): void
    {
        $address = $this->createMock(AddressInterface::class);

        $addressManager = $this->createMock(AddressManagerInterface::class);
        $addressManager->expects(static::once())->method('findOneBy')->willReturn($address);
        $addressManager->expects(static::once())->method('delete');

        $view = $this->createAddressController($address, $addressManager)->deleteAddressAction(1);

        static::assertSame(['deleted' => true], $view);
    }

    public function testDeleteAddressInvalidAction(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $addressManager = $this->createMock(AddressManagerInterface::class);
        $addressManager->expects(static::once())->method('findOneBy')->willReturn(null);
        $addressManager->expects(static::never())->method('delete');

        $this->createAddressController(null, $addressManager)->deleteAddressAction(1);
    }

    /**
     * Returns address controller.
     *
     * @param \Sonata\Component\Customer\AddressInterface        $address
     * @param \Sonata\Component\Customer\AddressManagerInterface $addressManager
     * @param \Symfony\Component\Form\FormFactory                $formFactory
     *
     * @return AddressController
     */
    public function createAddressController($address = null, $addressManager = null, $formFactory = null)
    {
        if (null === $addressManager) {
            $addressManager = $this->createMock(AddressManagerInterface::class);

            if ($address) {
                $addressManager->expects(static::once())->method('findOneBy')->willReturn($address);
            }
        }

        if (null === $formFactory) {
            $formFactory = $this->createMock(FormFactoryInterface::class);
        }

        return new AddressController($addressManager, $formFactory);
    }
}
