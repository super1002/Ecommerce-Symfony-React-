<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\ProductBundle\Model;

use Sonata\Component\Product\ProductInterface;
use Sonata\Component\Order\OrderInterface;
use Sonata\Component\Order\OrderElementInterface;
use Sonata\Component\Delivery\DeliveryInterface;
use Sonata\Component\Product\ProductProviderInterface;
use Sonata\Component\Basket\BasketElementInterface;
use Sonata\Component\Basket\BasketInterface;
use Sonata\Component\Basket\BasketElement;
use Sonata\AdminBundle\Validator\ErrorElement;
use Symfony\Component\Form\FormBuilder;
use Sonata\AdminBundle\Form\FormMapper;

use Application\Sonata\OrderBundle\Entity\OrderElement;
use Sonata\Component\Basket\BasketElementManagerInterface;

use JMS\SerializerBundle\Serializer\SerializerInterface;

abstract class BaseProductProvider implements ProductProviderInterface
{
    protected $options           = array();

    protected $variationFields   = array();

    protected $code;

    protected $serializer;

    protected $basketElementManager;

    /**
     * @param \JMS\SerializerBundle\Serializer\SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param \Sonata\Component\Basket\BasketElementManagerInterface $basketElementManager
     * @return void
     */
    function setBasketElementManager(BasketElementManagerInterface $basketElementManager)
    {
        $this->basketElementManager = $basketElementManager;
    }

    /**
     * @return \Sonata\Component\Basket\BasketElementManagerInterface
     */
    function getBasketElementManager()
    {
        return $this->basketElementManager;
    }

    /**
     * @param array $options
     * @return void
     */
    public function setOptions(array $options = array())
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return array|null
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    ////////////////////////////////////////////////
    //   ORDER RELATED FUNCTIONS

    /**
     * @param \Sonata\Component\Basket\BasketElementInterface $basketElement
     * @return \Sonata\Component\Order\OrderElementInterface
     */
    public function createOrderElement(BasketElementInterface $basketElement)
    {
        $orderElement = new OrderElement;
        $orderElement->setQuantity($basketElement->getQuantity());
        $orderElement->setPrice($basketElement->getTotal(false));
        $orderElement->setVat($basketElement->getVat());
        $orderElement->setDesignation($basketElement->getName());
        $orderElement->setProductType($this->getCode());
        $orderElement->setStatus(OrderInterface::STATUS_PENDING);
        $orderElement->setDeliveryStatus(DeliveryInterface::STATUS_OPEN);
        $orderElement->setCreatedAt(new \DateTime);
        $orderElement->setOptions($basketElement->getOptions());

        $product = $basketElement->getProduct();
        $orderElement->setDescription($product->getDescription());
        $orderElement->setProductId($product->getId());
        $orderElement->setRawProduct($this->getRawProduct($product, 'json'));

        return $orderElement;
    }

    /**
     * @param \Sonata\Component\Product\ProductInterface $product
     * @param string $format
     * @return array
     */
    public function getRawProduct(ProductInterface $product, $format = 'json')
    {
        return json_decode($this->serializer->serialize($product, $format), true);
    }

    /**
     * @param \Application\Sonata\OrderBundle\Entity\OrderElement $orderElement
     * @param string $type
     * @param string $format
     * @return \Sonata\Component\Product\ProductInterface
     */
    public function getProductFromRaw(OrderElement $orderElement, $type, $format = 'json')
    {
        return $this->serializer->deserialize(json_encode($orderElement->getRawProduct()), $type, $format);
    }

    ////////////////////////////////////////////////
    //   VARIATION RELATED FUNCTIONS

    /**
     * @param  $name
     * @return bool return true if the field $name is a variation
     */
    public function isVariateBy($name)
    {
        return in_array($name, $this->variationFields);
    }

    /**
     * @return bool return true if the product haas some variation fields
     */
    public function hasVariationFields()
    {
        return count($this->getVariationFields()) > 0;
    }

    /**
     * @param array $fields
     * @return void
     */
    public function setVariationFields(array $fields = array())
    {
        $this->variationFields = $fields;
    }

    /**
     * @return array
     */
    public function getVariationFields()
    {
        return $this->variationFields;
    }

    /**
     * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
     * @return void
     */
    public function buildEditForm(FormMapper $formMapper)
    {
        $formMapper
            ->add('name')
            ->add('sku')
            ->add('description')
            ->add('price', 'number')
            ->add('vat', 'number')
            ->add('stock', 'integer')
            ->add('image', 'sonata_type_model', array(), array(
                'edit' => 'list',
                'link_parameters' => array(
                    'context' => 'sonata_product',
                    'filter' => array('context' => array('value' => 'sonata_product'))
                )
            ))
        ;
    }

    /**
     * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
     * @return void
     */
    public function buildCreateForm(FormMapper $formMapper)
    {
        $this->buildEditForm($formMapper);
    }

    /**
     * @throws \RuntimeException
     * @param \Sonata\Component\Product\ProductInterface $product
     * @return \Sonata\Component\Product\ProductInterface
     */
    public function createVariation(ProductInterface $product)
    {
        if ($product->isVariation()) {
            throw new \RuntimeException('Cannot create a variation from a variation product');
        }

        $variation = clone $product;
        $variation->setParent($product);
        $variation->setId(null);
        $variation->setEnabled(false);
        $variation->setName(sprintf('%s (duplicated)', $product->getName()));

        return $variation;
    }

    /**
     * @param \Sonata\Component\Product\ProductInterface $product
     * @param string $name
     * @param bool $forceCopy
     * @return
     */
    public function copyVariation(ProductInterface $product, $name = 'all', $forceCopy = false)
    {
        if ($product->isVariation()) {
            return;
        }

        switch ($name) {
            case 'product':
                $this->copyProductVariation($product, $forceCopy);
                return;

            case 'all':
                $this->copyProductVariation($product, $forceCopy);
                return;
        }
    }

    /**
     * @param \Sonata\Component\Product\ProductInterface $product
     * @param bool $forceCopy
     * @return void
     */
    public function copyProductVariation(ProductInterface $product, $forceCopy = false)
    {
        $variationFields = array_merge(array('id'), $this->getVariationFields());

        // fields to copy
        $values = array(
            'Name'    => $product->getName(),
            'Price'   => $product->getPrice(),
            'Vat'     => $product->getVat(),
            'Enabled' => $product->getEnabled()
        );

        if (!$forceCopy) {
            foreach ($variationFields as $field) {

                if (!array_key_exists($field, $values)) {
                   continue;
                }

                unset($values[$field]);
            }
        }

        foreach ($product->getVariations() as $variation) {
            foreach ($values as $name => $value) {
                call_user_func(array($variation, 'set'.$name), $value );
            }
        }
    }

    /////////////////////////////////////////////////////
    // BASKET RELATED FUNCTIONS

    public function createBasketElement(ProductInterface $product = null, array $options = array())
    {
        $basketElement = $this->getBasketElementManager()->create();
        $this->buildBasketElement($basketElement, $product, $options);

        return $basketElement;
    }

    /**
     * @param \Sonata\Component\Basket\BasketElementInterface $basketElement
     * @param null|\Sonata\Component\Product\ProductInterface $product
     * @param array $options
     * @return void
     */
    public function buildBasketElement(BasketElementInterface $basketElement, ProductInterface $product = null, array $options = array())
    {
        if ($product) {
            $basketElement->setProduct($this->code, $product);

            if (!$basketElement->getQuantity()) {
                $basketElement->setQuantity(1);
            }
        }

        $basketElement->setOptions($options);
    }

    /**
     * This function return the form used in the product view page
     *
     * @param \Sonata\Component\Product\ProductInterface $product
     * @param \Symfony\Component\Form\FormBuilder $formBuilder
     * @param array $options
     * @return void
     */
    public function defineAddBasketForm(ProductInterface $product, FormBuilder $formBuilder, array $options = array())
    {
        $basketElement = $this->createBasketElement($product);

        // create the product form
        $formBuilder
            ->setData($basketElement)
            ->add('quantity', 'text')
            ->add('productId', 'hidden');
    }

    /**
     * @param \Sonata\Component\Basket\BasketElementInterface $basketElement
     * @param \Symfony\Component\Form\FormBuilder $formBuilder
     * @param array $options
     * @return void
     */
    public function defineBasketElementForm(BasketElementInterface $basketElement, FormBuilder $formBuilder, array $options = array())
    {
        $formBuilder
            ->add('delete', 'checkbox')
            ->add('quantity', 'text')
            ->add('productId', 'hidden');
    }

    /**
     * return an array of errors if any, you can also manipulate the basketElement if require
     * please not you always work with a clone version of the basketElement.
     *
     * If the basket is valid it will then replace the one in session
     *
     * @param \Sonata\AdminBundle\Validator\ErrorElement $errorElement
     * @param \Sonata\Component\Basket\BasketElementInterface $basketElement
     * @param \Sonata\Component\Basket\BasketInterface $basket
     * @return void
     */
    public function validateFormBasketElement(ErrorElement $errorElement, BasketElementInterface $basketElement, BasketInterface $basket)
    {
        // the item is flagged as deleted, no need to validate the item
        if ($basketElement->getDelete()) {
            return;
        }

        // refresh the product from the database
        $product = $basketElement->getProduct();

        // check if the product is still in database
        if (!$product) {
            $errorElement->addViolation('The product is not available anymore');

            return;
        }

        // check if the product is still enabled
        if (!$basketElement->getProduct()->getEnabled()) {
            $errorElement->addViolation('The product is not enabled anymore');

            return;
        }

        // check if the quantity is numeric
        if (!is_numeric($basketElement->getQuantity())) {
            $errorElement
                ->with('quantity')
                    ->addViolation('The product quantity is not a numeric value')
                ->end();

            return;
        }

        $errorElement
            ->with('quantity')
                ->assertMin(
                    array('limit' => 1),
                    'The product quantity ({{ quantity }}) is not valid',
                    array('{{ quantity }}' => $basketElement->getQuantity())
                )
                ->assertMax(
                    array('limit' => $this->getStockAvailable($basketElement->getProduct())),
                    'The product quantity ({{ quantity }}) is not valid',
                    array('{{ quantity }}' => $basketElement->getQuantity())
                )
            ->end();
    }

    /**
     * Returns true if the basket element is still valid
     *
     * @param \Sonata\Component\Basket\BasketInterface $basket
     * @param \Sonata\Component\Product\ProductInterface $product
     * @param \Sonata\Component\Basket\BasketElementInterface $basketElement
     * @return bool|\Sonata\Component\Basket\BasketElementInterface
     */
    public function basketAddProduct(BasketInterface $basket, ProductInterface $product, BasketElementInterface $basketElement)
    {
        if ($basket->hasProduct($product)) {
            return false;
        }

        $basketElementOptions = $product->getOptions();
        // add the default product options to the basket element
        if (is_array($basketElementOptions) && !empty($basketElementOptions)) {
            foreach ($basketElementOptions as $option => $value) {
                $basketElement->setOption($option, $value);
            }
        }

        $basket->addBasketElement($basketElement);

        return $basketElement;
    }


    /**
     * Merge a product with another when the product is already present into the basket
     *
     * @throws \RuntimeExeption
     * @param \Sonata\Component\Basket\BasketInterface $basket
     * @param \Sonata\Component\Product\ProductInterface $product
     * @param \Sonata\Component\Basket\BasketElementInterface $newBasketElement
     * @return bool|\Sonata\Component\Basket\Product
     */
    public function basketMergeProduct(BasketInterface $basket, ProductInterface $product, BasketElementInterface $newBasketElement)
    {
        if (!$basket->hasProduct($product)) {
            return false;
        }

        $basketElement = $basket->getElement($product);
        if (!$basketElement) {
            throw new \RuntimeException('no basket element related to product.id : %s', $product->getId());
        }

        $basketElement->setQuantity($basketElement->getQuantity() + $newBasketElement->getQuantity());

        return $basketElement;
    }

    /**
     * @param \Sonata\Component\Basket\BasketElementInterface $basketElement
     *
     * @return boolean true if the basket element is still valid
     */
    public function isValidBasketElement(BasketElementInterface $basketElement)
    {
        $product = $basketElement->getProduct();

        if (!$product instanceof ProductInterface) {
            return false;
        }

        return true;
    }

    /**
     * @param \Sonata\Component\Basket\BasketInterface $basket
     * @param \Sonata\Component\Basket\BasketElementInterface $basketElement
     *
     * @return float price of the basket element
     */
    public function basketCalculatePrice(BasketInterface $basket, BasketElementInterface $basketElement)
    {
        return $basketElement->getProduct()->getPrice();
    }

    /**
     * Return true if the product can be added to the provided basket
     *
     * @abstract
     * @param \Sonata\Component\Basket\BasketInterface $basket
     * @param \Sonata\Component\Product\ProductInterface $product
     * @param array $options
     * @return boolean
     */
    public function isAddableToBasket(BasketInterface $basket, ProductInterface $product, array $options = array())
    {
        return true;
    }

    /**
     * return a fresh product instance (so information are reloaded: enabled and stock ...)
     *
     * @param \Sonata\Component\Product\ProductInterface $product
     * @return \Sonata\Component\Product\ProductInterface
     */
    public function reloadProduct(ProductInterface $product)
    {
        return $this->findOneById($product->getId());
    }

    /**
     * @param integer $id
     * @return bool
     */
    public function findOneById($id)
    {
        $results = $this->createQueryBuilder('p')
            ->addSelect('i')
            ->leftJoin('p.image', 'i')
            ->andWhere('p.id = :id')
            ->getQuery()
            ->setParameters(array('id' => $id))
            ->setMaxResults(1)
            ->execute();

        return count($results) > 0 ? $results[0] : false;
    }

    /**
     * return the stock available for the current product
     *
     * @param \Sonata\Component\Product\ProductInterface $product
     * @return int the stock available
     */
    public function getStockAvailable(ProductInterface $product)
    {
        return $product->getStock();
    }

    /**
     * @param $code
     * @return void
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return
     */
    public function getCode()
    {
        return $this->code;
    }
}