<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Railroad\Ecommerce\Entities\Product;

/**
 * Local DTO object returned by CartService
 */
class AddCartItemsResult
{
    private $success;

    private $addedProducts;

    private $errors;

    public function __construct()
    {
        $this->success = false;
        $this->addedProducts = [];
        $this->errors = [];
    }

    public function getSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $resultSuccessful): AddCartItemsResult
    {
        $this->success = $resultSuccessful;

        return $this;
    }

    public function getAddedProducts(): array
    {
        return $this->addedProducts;
    }

    public function setAddedProducts(array $addedProducts): AddCartItemsResult
    {
        $this->addedProducts = $addedProducts;

        return $this;
    }

    public function addProduct(Product $product): AddCartItemsResult
    {
        $this->addedProducts[] = $product;

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): AddCartItemsResult
    {
        $this->errors = $errors;

        return $this;
    }

    public function addError(array $error): AddCartItemsResult
    {
        $this->errors[] = $error;

        return $this;
    }
}
