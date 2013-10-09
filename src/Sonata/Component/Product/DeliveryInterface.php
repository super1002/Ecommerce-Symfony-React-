<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Component\Product;

interface DeliveryInterface
{
    /**
     * Set productId
     *
     * @param integer $productId
     */
    public function setProduct(ProductInterface $product);

    /**
     * Get productId
     *
     * @return integer $productId
     */
    public function getProduct();

    /**
     * Set class_name
     *
     * @param string $className
     */
    public function setCode($code);

    /**
     * Get class_name
     *
     * @return string $className
     */
    public function getCode();

    /**
     * Set per_item
     *
     * @param boolean $perItem
     */
    public function setPerItem($perItem);

    /**
     * Get per_item
     *
     * @return boolean $perItem
     */
    public function getPerItem();

    /**
     * Set country
     *
     * @param string $country
     */
    public function setCountryCode($countryCode);

    /**
     * Get country
     *
     * @return string $country
     */
    public function getCountryCode();

    /**
     * Set zone
     *
     * @param string $zone
     */
    public function setZone($zone);

    /**
     * Get zone
     *
     * @return string $zone
     */
    public function getZone();

    /**
     * Set enabled
     *
     * @param boolean $enabled
     */
    public function setEnabled($enabled);

    /**
     * Get enabled
     *
     * @return boolean $enabled
     */
    public function getEnabled();

    /**
     * Set updatedAt
     *
     * @param \Datetime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt = null);

    /**
     * Get updatedAt
     *
     * @return \Datetime $updatedAt
     */
    public function getUpdatedAt();

    /**
     * Set createdAt
     *
     * @param \Datetime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt = null);

    /**
     * Get createdAt
     *
     * @return \Datetime $createdAt
     */
    public function getCreatedAt();
}
