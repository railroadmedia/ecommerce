<?php


namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\CustomerRepository;

class CustomerService
{
    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * CustomerService constructor.
     * @param CustomerRepository $customerRepository
     */
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function store($phone, $email, $brand)
    {
        $customerId = $this->customerRepository->create([
            'phone' => $phone,
            'email' => $email,
            'brand' => $brand ?? ConfigService::$brand,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);
         return $this->getById($customerId);
    }

    public function getById($id)
    {
        return $this->customerRepository->getById($id);
    }

}