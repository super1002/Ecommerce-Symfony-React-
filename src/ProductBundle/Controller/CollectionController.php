<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\ProductBundle\Controller;

use Sonata\DoctrineORMAdminBundle\Datagrid\Pager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CollectionController extends Controller
{
    /**
     * List the main collections.
     *
     * @return Response
     */
    public function indexAction()
    {
        $pager = $this->get('sonata.classification.manager.collection')
            ->getRootCollectionsPager($this->getCurrentRequest()->get('page'));

        return $this->render('SonataProductBundle:Collection:index.html.twig', [
            'pager' => $pager,
        ]);
    }

    /**
     * Display one collection.
     *
     *
     * @param $collectionId
     * @param $slug
     *
     * @throws NotFoundHttpException
     *
     * @return Response
     */
    public function viewAction($collectionId, $slug)
    {
        $collection = $this->get('sonata.classification.manager.collection')->findOneBy(['id' => $collectionId]);

        if (!$collection) {
            throw new NotFoundHttpException(sprintf('Unable to find the collection with id=%d', $collectionId));
        }

        return $this->render('SonataProductBundle:Collection:view.html.twig', [
           'collection' => $collection,
        ]);
    }

    /**
     * List collections from one collections.
     *
     * @param $collectionId
     *
     * @return Response
     */
    public function listSubCollectionsAction($collectionId)
    {
        $pager = $this->get('sonata.classification.manager.collection')
            ->getSubCollectionsPager($collectionId, $this->getCurrentRequest()->get('page'));

        return $this->render('SonataProductBundle:Collection:list_sub_collections.html.twig', [
            'pager' => $pager,
        ]);
    }

    /**
     * List the product related to one collection.
     *
     * @param $collectionId
     *
     * @return Response
     */
    public function listProductsAction($collectionId)
    {
        $pager = $this->get('sonata.product.set.manager')
            ->getProductsByCollectionIdPager($collectionId, $this->getCurrentRequest()->get('page'));

        return $this->render('SonataProductBundle:Collection:list_products.html.twig', [
            'pager' => $pager,
        ]);
    }

    /**
     * @param null $collection
     * @param int  $depth
     * @param int  $deep
     *
     * @return Response
     */
    public function listSideMenuCollectionsAction($collection = null, $depth = 1, $deep = 0)
    {
        $collection = $collection ?: $this->get('sonata.classification.manager.collection')->getRootCollection();

        return $this->render('SonataProductBundle:Collection:side_menu_collection.html.twig', [
          'root_collection' => $collection,
          'depth' => $depth,
          'deep' => $deep + 1,
        ]);
    }

    /**
     * NEXT_MAJOR: Remove this method (inject Request $request into actions parameters).
     *
     * @return Request
     */
    private function getCurrentRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }
}
