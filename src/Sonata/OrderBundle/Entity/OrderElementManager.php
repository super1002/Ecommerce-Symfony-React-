<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sonata\OrderBundle\Entity;

use Sonata\Component\Order\OrderElementManagerInterface;
use Sonata\Component\Order\OrderElementInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class OrderElementManager implements OrderElementManagerInterface
{
    protected $em;
    protected $repository;
    protected $class;
    
    public function __construct(EntityManager $em, $class)
    {
        $this->em    = $em;
        $this->class = $class;

        if(class_exists($class)) {
            $this->repository = $this->em->getRepository($class);
        }
    }

    /**
     * Creates an empty orderElement instance
     *
     * @return OrderElement
     */
    public function createOrderElement()
    {
        $class = $this->class;

        return new $class;
    }

    /**
     * Updates a orderElement
     *
     * @param OrderElement $orderElement
     * @return void
     */
    public function updateOrderElement(OrderElementInterface $orderElement)
    {
        $this->em->persist($orderElement);
        $this->em->flush();
    }

    /**
     * Returns the orderElement's fully qualified class name
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Finds one orderElement by the given criteria
     *
     * @param array $criteria
     * @return OrderElement
     */
    public function findOrderElementBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }

    /**
     * Deletes an orderElement
     *
     * @param OrderElement $orderElement
     * @return void
     */
    public function deleteOrderElement(OrderElementInterface $orderElement)
    {
        $this->em->remove($orderElement);
        $this->em->flush();
    }
}