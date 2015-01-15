<?php

namespace Sonata\Component\Basket;

use Sonata\Component\Customer\CustomerInterface;
use Doctrine\ORM\NoResultException;
use Sonata\CoreBundle\Model\BaseEntityManager;
use Sonata\DatagridBundle\Pager\Doctrine\Pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;

class BasketManager extends BaseEntityManager implements BasketManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadBasketPerCustomer(CustomerInterface $customer)
    {
        try {
            return $this->getRepository()->createQueryBuilder('b')
                ->leftJoin('b.basketElements', 'be', null, null, 'be.position')
                ->where('b.customer = :customer')
                ->setParameter('customer', $customer->getId())
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, $andFlush = true)
    {
        foreach ($entity->getBasketElements() as $element) {
            $element->setBasket($entity);
        }

        parent::save($entity, $andFlush);
    }

    /**
     * {@inheritdoc}
     */
    public function getPager(array $criteria, $page, $limit = 10, array $sort = array())
    {
        $query = $this->getRepository()
            ->createQueryBuilder('b')
            ->select('b');

        $fields = $this->getEntityManager()->getClassMetadata($this->class)->getFieldNames();
        foreach ($sort as $field => $direction) {
            if (!in_array($field, $fields)) {
                unset($sort[$field]);
            }
        }
        if (count($sort) == 0) {
            $sort = array('id' => 'ASC');
        }
        foreach ($sort as $field => $direction) {
            $query->orderBy(sprintf('b.%s', $field), strtoupper($direction));
        }

        $pager = new Pager();
        $pager->setMaxPerPage($limit);
        $pager->setQuery(new ProxyQuery($query));
        $pager->setPage($page);
        $pager->init();

        return $pager;
    }
}
