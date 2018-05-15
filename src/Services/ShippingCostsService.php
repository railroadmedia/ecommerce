<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\ShippingCostsRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;

class ShippingCostsService
{
    /**
     * @var ShippingCostsRepository
     */
    private $shippingCostsRepository;

    /**
     * @var ShippingOptionRepository
     */
    private $shippingRepository;

    /**
     * ShippingCostsService constructor.
     * @param $shippingCostsRepository
     */
    public function __construct(
        ShippingCostsRepository $shippingCostsRepository,
        ShippingOptionRepository $shippingRepository)
    {
        $this->shippingCostsRepository = $shippingCostsRepository;
        $this->shippingRepository = $shippingRepository;
    }

    /** Call the method that save a shipping costs weight range for a shipping option in the database if the shipping option exist.
     *  Return null if the shipping option not exist
     *         array with the new created shipping cost weight range
     * @param int $shippingOptionId
     * @param float $min
     * @param float $max
     * @param float $price
     * @return array|null
     */
    public function store($shippingOptionId, $min, $max, $price)
    {
        $shippingOption = $this->shippingRepository->getById($shippingOptionId);

        if (is_null($shippingOption)) {
            return null;
        }

        $shippingCostsId = $this->shippingCostsRepository->create([
            'shipping_option_id' => $shippingOptionId,
            'min' => $min,
            'max' => $max,
            'price' => $price,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);

        return $this->getById($shippingCostsId);
    }

    /** Update the shipping costs data if exist in the database.
     *  Return null if the shipping cost not exist in the database
     *         array with the updated shipping costs
     * @param integer $id
     * @param array $data
     * @return array|null
     */
    public function update($id, array $data)
    {
        $shippingCosts = $this->getById($id);

        if (empty($shippingCosts)) {
            return null;
        }

        $data['updated_on'] = Carbon::now()->toDateTimeString();
        $this->shippingCostsRepository->update($id, $data);

        return $this->getById($id);
    }


    /** Get the shipping costs data based on id
     * Return an array with shipping costs details
     * @param int $shippingCostsId
     * @return array
     */
    public function getById($shippingCostsId)
    {
        return $this->shippingCostsRepository->getById($shippingCostsId);
    }

    /** Delete the shipping cost record from database if exist.
     * Return null if the shipping cost id not exist in the database
     *        true if the shipping costs was deleted
     * @param int $shippingCostsId
     * @return bool|null
     */
    public function delete($shippingCostsId)
    {
        $shippingCosts = $this->getById($shippingCostsId);

        if (empty($shippingCosts)) {
            return null;
        }

        return $this->shippingCostsRepository->delete($shippingCostsId);
    }
}