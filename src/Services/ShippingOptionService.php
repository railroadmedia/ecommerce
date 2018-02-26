<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\ShippingCostsRepository;
use Railroad\Ecommerce\Repositories\ShippingRepository;

class ShippingOptionService
{
    /**
     * @var ShippingRepository
     */
    private $shippingRepository;

    /**
     * @var ShippingCostsRepository
     */
    private $shippingCostsRepository;

    /**
     * ShippingOptionService constructor.
     * @param $shippingOptionRepository
     */
    public function __construct(ShippingRepository $shippingOptionRepository, ShippingCostsRepository $shippingCostsRepository)
    {
        $this->shippingRepository = $shippingOptionRepository;
        $this->shippingCostsRepository = $shippingCostsRepository;
    }

    /** Call the method that save a new shipping option in the database.
     * Return an array with the shipping option details
     * @param string $country
     * @param integer $priority
     * @param bool $active
     * @return array
     */
    public function store($country, $priority, $active)
    {
        $shippingOptionId = $this->shippingRepository->create([
            'country' => $country,
            'priority' => $priority,
            'active' => $active,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);

        return $this->getById($shippingOptionId);
    }

    /** Update a shipping option if exist in the database.
     * Return null if the shipping option not exist in the database
     *        an array with the updated shipping option details
     * @param integer $id
     * @param array $data
     * @return array|null
     */
    public function update($id, array $data)
    {
        $shippingOption = $this->getById($id);

        if (empty($shippingOption)) {
            return null;
        }

        $data['updated_on'] = Carbon::now()->toDateTimeString();
        $this->shippingRepository->update($id, $data);

        return $this->getById($id);
    }

    /** Return an array with the shipping option details.
     * @param integer $shippingOptionId
     * @return array
     */
    public function getById($shippingOptionId)
    {
        return $this->shippingRepository->getById($shippingOptionId);
    }

    /** If the shipping option exists delete the shipping option and the associated shipping costs weight range.
     * Return null if the shipping option id not exist in the database
     *        true if the shipping option was deleted
     * @param integer $shippingOptionId
     * @return bool|null
     */
    public function delete($shippingOptionId)
    {
        $shippingOption = $this->getById($shippingOptionId);

        if (empty($shippingOption)) {
            return null;
        }

        $shippingCosts = $this->shippingRepository->getShippingCostsForShippingOption($shippingOptionId);
        if (!empty($shippingCosts)) {
            foreach ($shippingCosts as $shippingCost) {
                //delete shipping costs
                $this->shippingCostsRepository->delete($shippingCost['id']);
            }
        }
        return $this->shippingRepository->delete($shippingOptionId);
    }
}