<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sonata\Tests\Component\Basket;

use Sonata\Component\Basket\BasketManager;


/**
 * Class BasketManagerTest
 *
 * @package Sonata\Tests\Component\Basket
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class BasketManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateAndGetClass()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $basketMgr = new BasketManager($em, 'Sonata\Component\Basket\Basket');

        $this->assertInstanceOf('Sonata\Component\Basket\Basket', $basketMgr->create());
        $this->assertEquals('Sonata\Component\Basket\Basket', $basketMgr->getClass());
    }

    public function testSave()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $basketMgr = new BasketManager($em, 'Sonata\Component\Basket\Basket');

        $basketElement = $this->getMock('Sonata\Component\Basket\BasketInterface');
        $basketMgr->save($basketElement);
    }

    public function testGetRepository()
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method('getRepository')->will($this->returnValue($repository));

        $basketMgr = new BasketManager($em, 'Sonata\Component\Basket\Basket');

        $this->assertInstanceOf('Doctrine\ORM\EntityRepository', $basketMgr->getRepository());
    }

    public function testFind()
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('findOneBy');
        $repository->expects($this->once())->method('findBy');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->any())->method('getRepository')->will($this->returnValue($repository));

        $basketMgr = new BasketManager($em, 'Sonata\Component\Basket\Basket');
        $basketMgr->findBy(array());
        $basketMgr->findOneBy(array());
    }

    public function testDelete()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method('remove');
        $em->expects($this->once())->method('flush');

        $basketMgr = new BasketManager($em, 'Sonata\Component\Basket\Basket');

        $basketElement = $this->getMock('Sonata\Component\Basket\BasketInterface');
        $basketMgr->delete($basketElement);
    }
}
