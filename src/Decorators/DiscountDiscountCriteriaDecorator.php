<?php

namespace Railroad\Ecommerce\Decorators;


use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Resora\Decorators\DecoratorInterface;

class DiscountDiscountCriteriaDecorator implements DecoratorInterface
{
    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountCriteriaRepository
     */
    private $discountCriteriaRepository;

    public function __construct(DiscountCriteriaRepository $discountCriteriaRepository)
    {
        $this->discountCriteriaRepository = $discountCriteriaRepository;
    }

    public function decorate($discounts)
    {
        $discountIds = $discounts->pluck('id');

        $discountCriteria = $this->discountCriteriaRepository
            ->query()
            ->whereIn('discount_id', $discountIds->toArray())
            ->get();

        foreach ($discounts as $discountIndex => $discount) {
            $discounts[$discountIndex]['criteria'] = [];
            foreach ($discountCriteria as $discountCriteriaIndex => $discountCriteriaItem) {
                $discountCriteriaItem = (array)$discountCriteriaItem;
                if ($discountCriteriaItem['discount_id'] == $discount['id']) {
                    $discounts[$discountIndex]['criteria'][] = $discountCriteriaItem;
                }
            }
        }

        return $discounts;
    }
}