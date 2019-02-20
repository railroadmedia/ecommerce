<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\Product;

class CartItem
{
    public $id;
    public $name;
    public $description;
    public $quantity;
    public $price;
    public $totalPrice;
    public $requiresShippingAddress;
    public $requiresBillingAddress;
    public $subscriptionIntervalType;
    public $subscriptionIntervalCount;
    public $discountedPrice;
    public $appliedDiscounts;
    public $product;
    public $options = [];

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param mixed $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return boolean
     */
    public function getRequiresShippingAddress()
    {
        return $this->requiresShippingAddress;
    }

    /**
     * @param boolean $requiresShippingAddress
     */
    public function setRequiresShippingAddress($requiresShippingAddress)
    {
        $this->requiresShippingAddress = $requiresShippingAddress;
    }

    /**
     * @return boolean
     */
    public function getRequiresBillingAddress()
    {
        return $this->requiresBillingAddress;
    }

    /**
     * @param boolean $requiresBillingAddress
     */
    public function setRequiresBillingAddress($requiresBillingAddress)
    {
        $this->requiresBillingAddress = $requiresBillingAddress;
    }

    /**
     * @return mixed
     */
    public function getSubscriptionIntervalType()
    {
        return $this->subscriptionIntervalType;
    }

    /**
     * @param mixed $subscriptionIntervalType
     */
    public function setSubscriptionIntervalType($subscriptionIntervalType)
    {
        $this->subscriptionIntervalType = $subscriptionIntervalType;
    }

    /**
     * @return mixed
     */
    public function getSubscriptionIntervalCount()
    {
        return $this->subscriptionIntervalCount;
    }

    /**
     * @param mixed $subscriptionIntervalCount
     */
    public function setSubscriptionIntervalCount($subscriptionIntervalCount)
    {
        $this->subscriptionIntervalCount = $subscriptionIntervalCount;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return mixed
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * @param mixed $price
     */
    public function setTotalPrice($totalPrice)
    {
        $this->totalPrice = $totalPrice;
    }

    /**
     * @return Product|null
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @param $price
     */
    public function setDiscountedPrice($price)
    {
        $this->discountedPrice = $price;
    }

    /**
     * @return mixed
     */
    public function getDiscountedPrice()
    {
        return $this->discountedPrice;
    }

    /**
     * @return array - of Discount
     */
    public function getAppliedDiscounts()
    {
        return $this->appliedDiscounts ?? [];
    }

    /**
     * @param Discount $discount
     */
    public function addAppliedDiscount(Discount $discount)
    {
        $this->appliedDiscounts[] = $discount;
    }
}