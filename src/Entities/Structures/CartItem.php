<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Serializable;

class CartItem implements Serializable
{
    /**
     * @var string
     */
    private $sku;

    /**
     * @var integer
     */
    private $quantity;

    /**
     * CartItem constructor.
     *
     * @param string $sku
     * @param int $quantity
     */
    public function __construct(string $sku, int $quantity)
    {
        $this->sku = $sku;
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @param string $sku
     */
    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(['sku' => $this->getSku(), 'quantity' => $this->getQuantity()]);
    }

    /**
     * @param $data
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        $this->setSku($data['sku']);
        $this->setQuantity($data['quantity']);
    }
}