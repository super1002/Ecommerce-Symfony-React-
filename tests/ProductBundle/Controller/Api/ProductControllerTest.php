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

namespace Sonata\ProductBundle\Tests\Controller\Api;

use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sonata\ClassificationBundle\Model\CategoryInterface;
use Sonata\ClassificationBundle\Model\CollectionInterface;
use Sonata\Component\Product\DeliveryInterface;
use Sonata\Component\Product\PackageInterface;
use Sonata\Component\Product\Pool as ProductPool;
use Sonata\Component\Product\ProductCategoryInterface;
use Sonata\Component\Product\ProductCollectionInterface;
use Sonata\Component\Product\ProductInterface;
use Sonata\Component\Product\ProductManagerInterface;
use Sonata\DatagridBundle\Pager\PagerInterface;
use Sonata\FormatterBundle\Formatter\Pool as FormatterPool;
use Sonata\FormatterBundle\Formatter\RawFormatter;
use Sonata\ProductBundle\Controller\Api\ProductController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Twig\Template;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class ProductControllerTest extends TestCase
{
    public function testGetProductsAction(): void
    {
        $pager = $this->createStub(PagerInterface::class);
        $productManager = $this->createMock(ProductManagerInterface::class);
        $productManager->expects(static::once())->method('getPager')->willReturn($pager);

        $paramFetcher = $this->createMock(ParamFetcherInterface::class);
        $paramFetcher->expects(static::exactly(3))->method('get')->willReturn(1, 10, null);
        $paramFetcher->expects(static::once())->method('all')->willReturn([]);

        static::assertSame($pager, $this->createProductController(null, $productManager)->getProductsAction($paramFetcher));
    }

    public function testGetProductAction(): void
    {
        $product = $this->createMock(ProductInterface::class);
        static::assertSame($product, $this->createProductController($product)->getProductAction(1));
    }

    public function testGetProductActionNotFoundException(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Product (42) not found');

        $this->createProductController()->getProductAction(42);
    }

    public function testGetProductProductcategoriesAction(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $productCategory = $this->createMock(ProductCategoryInterface::class);
        $product->expects(static::once())->method('getProductCategories')->willReturn([$productCategory]);

        static::assertSame(
            [$productCategory],
            $this->createProductController($product)->getProductProductcategoriesAction(1)
        );
    }

    public function testGetProductCategoriesAction(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $category = $this->createMock(CategoryInterface::class);
        $product->expects(static::once())->method('getCategories')->willReturn([$category]);

        static::assertSame([$category], $this->createProductController($product)->getProductCategoriesAction(1));
    }

    public function testGetProductProductcollectionsAction(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $productCollection = $this->createMock(ProductCollectionInterface::class);
        $product->expects(static::once())->method('getProductCollections')->willReturn([$productCollection]);

        static::assertSame(
            [$productCollection],
            $this->createProductController($product)->getProductProductcollectionsAction(1)
        );
    }

    public function testGetProductCollectionsAction(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $collection = $this->createMock(CollectionInterface::class);
        $product->expects(static::once())->method('getCollections')->willReturn([$collection]);

        static::assertSame([$collection], $this->createProductController($product)->getProductCollectionsAction(1));
    }

    public function testGetProductPackagesAction(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $package = $this->createMock(PackageInterface::class);
        $product->expects(static::once())->method('getPackages')->willReturn([$package]);

        static::assertSame([$package], $this->createProductController($product)->getProductPackagesAction(1));
    }

    public function testGetProductDeliveriesAction(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $delivery = $this->createMock(DeliveryInterface::class);
        $product->expects(static::once())->method('getDeliveries')->willReturn([$delivery]);

        static::assertSame([$delivery], $this->createProductController($product)->getProductDeliveriesAction(1));
    }

    public function testGetProductVariationsAction(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $variation = $this->createMock(ProductInterface::class);
        $product->expects(static::once())->method('getVariations')->willReturn([$variation]);

        static::assertSame([$variation], $this->createProductController($product)->getProductVariationsAction(1));
    }

    public function testPostProductAction(): void
    {
        $product = $this->getValidProduct();

        $productManager = $this->createMock(ProductManagerInterface::class);
        $productManager->expects(static::once())->method('save')->willReturn($product);

        $productPool = $this->createMock(ProductPool::class);
        $productPool->expects(static::once())->method('getManager')->willReturn($productManager);

        $formatterPool = $this->getValidFormatter();

        $form = $this->createMock(Form::class);
        $form->expects(static::once())->method('handleRequest');
        $form->expects(static::once())->method('isSubmitted')->willReturn(true);
        $form->expects(static::once())->method('isValid')->willReturn(true);
        $form->expects(static::once())->method('getData')->willReturn($product);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(static::once())->method('createNamed')->willReturn($form);

        $view = $this->createProductController(null, $productManager, $productPool, $formFactory, $formatterPool)
            ->postProductAction('my.test.provider', new Request());

        static::assertInstanceOf(View::class, $view);
    }

    public function testPostProductInvalidAction(): void
    {
        $product = $this->createMock(ProductInterface::class);

        $productManager = $this->createMock(ProductManagerInterface::class);
        $productManager->expects(static::never())->method('save');

        $productPool = $this->createMock(ProductPool::class);
        $productPool->expects(static::once())->method('getManager')->willReturn($productManager);

        $formatterPool = new FormatterPool('whatever');

        $form = $this->createMock(Form::class);
        $form->expects(static::once())->method('handleRequest');
        $form->expects(static::once())->method('isSubmitted')->willReturn(true);
        $form->expects(static::once())->method('isValid')->willReturn(false);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(static::once())->method('createNamed')->willReturn($form);

        $view = $this->createProductController(null, $productManager, $productPool, $formFactory, $formatterPool)
            ->postProductAction('my.test.provider', new Request());

        static::assertInstanceOf(FormInterface::class, $view);
    }

    public function testPutProductAction(): void
    {
        $product = $this->getValidProduct();

        $productManager = $this->createMock(ProductManagerInterface::class);
        $productManager->expects(static::once())->method('findOneBy')->willReturn($product);
        $productManager->expects(static::once())->method('save')->willReturn($product);

        $productPool = $this->createMock(ProductPool::class);
        $productPool->expects(static::once())->method('getManager')->willReturn($productManager);

        $formatterPool = $this->getValidFormatter();

        $form = $this->createMock(Form::class);
        $form->expects(static::once())->method('handleRequest');
        $form->expects(static::once())->method('isSubmitted')->willReturn(true);
        $form->expects(static::once())->method('isValid')->willReturn(true);
        $form->expects(static::once())->method('getData')->willReturn($product);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(static::once())->method('createNamed')->willReturn($form);

        $view = $this->createProductController($product, $productManager, $productPool, $formFactory, $formatterPool)
            ->putProductAction(1, 'my.test.provider', new Request());

        static::assertInstanceOf(View::class, $view);
    }

    public function testPutProductInvalidAction(): void
    {
        $product = $this->createMock(ProductInterface::class);

        $productManager = $this->createMock(ProductManagerInterface::class);
        $productManager->expects(static::once())->method('findOneBy')->willReturn($product);
        $productManager->expects(static::never())->method('save');

        $productPool = $this->createMock(ProductPool::class);
        $productPool->expects(static::once())->method('getManager')->willReturn($productManager);

        $formatterPool = new FormatterPool('whatever');

        $form = $this->createMock(Form::class);
        $form->expects(static::once())->method('handleRequest');
        $form->expects(static::once())->method('isSubmitted')->willReturn(true);
        $form->expects(static::once())->method('isValid')->willReturn(false);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(static::once())->method('createNamed')->willReturn($form);

        $view = $this->createProductController($product, $productManager, $productPool, $formFactory, $formatterPool)
            ->putProductAction(1, 'my.test.provider', new Request());

        static::assertInstanceOf(FormInterface::class, $view);
    }

    public function testDeleteProductAction(): void
    {
        $product = $this->createMock(ProductInterface::class);

        $productManager = $this->createMock(ProductManagerInterface::class);
        $productManager->expects(static::once())->method('delete');

        $productPool = $this->createMock(ProductPool::class);
        $productPool->expects(static::once())->method('getManager')->willReturn($productManager);

        $view = $this->createProductController($product, $productManager, $productPool)->deleteProductAction(1);

        static::assertSame(['deleted' => true], $view);
    }

    public function testDeleteProductInvalidAction(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $product = $this->createMock(ProductInterface::class);

        $productManager = $this->createMock(ProductManagerInterface::class);
        $productManager->expects(static::once())->method('findOneBy')->willReturn(null);
        $productManager->expects(static::never())->method('delete');

        $productPool = $this->createMock(ProductPool::class);
        $productPool->expects(static::never())->method('getManager')->willReturn($productManager);

        $view = $this->createProductController($product, $productManager, $productPool)->deleteProductAction(1);

        static::assertSame(['deleted' => true], $view);
    }

    /**
     * @param $product
     * @param $productManager
     * @param $productPool
     * @param $formFactory
     * @param null $formatterPool
     *
     * @return ProductController
     */
    public function createProductController($product = null, $productManager = null, $productPool = null, $formFactory = null, $formatterPool = null)
    {
        if (null === $productManager) {
            $productManager = $this->createMock(ProductManagerInterface::class);
        }
        if (null !== $product) {
            $productManager->expects(static::once())->method('findOneBy')->willReturn($product);
        }
        if (null === $productPool) {
            $productPool = $this->createMock(ProductPool::class);
        }
        if (null === $formFactory) {
            $formFactory = $this->createMock(FormFactoryInterface::class);
        }
        if (null === $formatterPool) {
            $formatterPool = new FormatterPool('whatever');
        }

        return new ProductController($productManager, $productPool, $formFactory, $formatterPool);
    }

    private function getValidProduct(): ProductInterface
    {
        $product = $this->createMock(ProductInterface::class);
        $product->expects(static::once())
            ->method('getDescriptionFormatter')
            ->willReturn('foo');
        $product->expects(static::once())
            ->method('getRawDescription')
            ->willReturn('description');
        $product->expects(static::once())
            ->method('getShortDescriptionFormatter')
            ->willReturn('foo');
        $product->expects(static::once())
            ->method('getRawShortDescription')
            ->willReturn('shortDescription');

        return $product;
    }

    private function getValidFormatter(): FormatterPool
    {
        $formatter = new RawFormatter();
        $env = $this->createMock(Environment::class);
        $template = $this->createMock(Template::class);

        $template->expects(static::exactly(2))
            ->method('render')
            ->willReturn('Salut');
        $env->expects(static::exactly(2))
            ->method('createTemplate')
            ->willReturn($template);

        $formatterPool = new FormatterPool('whatever');
        $formatterPool->setLogger($this->createMock(LoggerInterface::class));
        $formatterPool->add('foo', $formatter, $env);

        return $formatterPool;
    }
}
