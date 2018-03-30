<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Permissions\Services\UserAccessService;
use Railroad\RemoteStorage\Services\RemoteStorageService;


class ProductService
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * @var DiscountCriteriaRepository
     */
    private $discountCriteriaRepository;

    /**
     * @var RemoteStorageService
     */
    private $remoteStorageService;

    /**
     * @var UserAccessService
     */
    private $userAccessService;

    /**
     * Determines whether inactive products will be pulled or not.
     *
     * @var array|bool
     */
    public static $pullInactiveProducts = true;

    // all possible product types
    const TYPE_PRODUCT = 'product';
    const TYPE_SUBSCRIPTION = 'subscription';

    /**
     * ProductService constructor.
     * @param $productRepository
     */
    public function __construct(ProductRepository $productRepository,
                                OrderItemRepository $orderItemRepository,
                                DiscountCriteriaRepository $discountCriteriaRepository,
                                RemoteStorageService $remoteStorageService,
                                UserAccessService $userAccessService)
    {
        $this->productRepository = $productRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->discountCriteriaRepository = $discountCriteriaRepository;
        $this->remoteStorageService = $remoteStorageService;
        $this->userAccessService = $userAccessService;
    }

    /** Get all the active products that meet the conditions
     * @param array $conditions
     * @return mixed
     */
    public function getProductByConditions(array $conditions)
    {
        return $this->productRepository->getProductsByConditions($conditions)[0] ?? null;
    }

    /** Create a new product and return it as array
     * @param string|null $brand
     * @param string $name
     * @param string $sku
     * @param numeric $price
     * @param string $type
     * @param boolean $active
     * @param string $description
     * @param string $thumbnail_url
     * @param boolean $is_physical
     * @param integer|null $weight
     * @param string|null $subscription_interval_type
     * @param integer|null $subscription_interval_count
     * @param integer $stock
     * @return array|null
     */
    public function store(
        $brand,
        $name,
        $sku,
        $price,
        $type,
        $active,
        $description,
        $thumbnail_url,
        $is_physical,
        $weight,
        $subscription_interval_type,
        $subscription_interval_count,
        $stock)
    {

        $productId = $this->productRepository->create([
            'brand' => $brand ?? ConfigService::$brand,
            'name' => $name,
            'sku' => $sku,
            'price' => $price,
            'type' => $type,
            'active' => $active,
            'description' => $description,
            'thumbnail_url' => $thumbnail_url,
            'is_physical' => $is_physical,
            'weight' => $weight,
            'subscription_interval_type' => ($type == self::TYPE_SUBSCRIPTION) ? $subscription_interval_type : null,
            'subscription_interval_count' => ($type == self::TYPE_SUBSCRIPTION) ? $subscription_interval_count : null,
            'stock' => $stock,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);

        return $this->getById($productId);
    }

    /** Get an array with product details based on product id
     * @param integer $productId
     * @return array|null
     */
    public function getById($productId)
    {
        return $this->productRepository->getById($productId);
    }

    /** Update and return the modified product. If the product not exist return null.
     * @param integer $id
     * @param array $data
     * @return array|null
     */
    public function update($id, array $data)
    {
        $product = $this->getById($id);

        if (empty($product)) {
            return null;
        }

        $data['updated_on'] = Carbon::now()->toDateTimeString();
        $this->productRepository->update($id, $data);

        return $this->getById($id);
    }

    /** Delete a product if it's not connected to orders or discounts.
     *  Return - null if the product not exists
     *         - 0 if the product it's connected with orders
     *         - -1 if the product it's connected with discounts
     *          - true if the product was deleted
     * @param integer $productId
     * @return bool|int|null
     */
    public function delete($productId)
    {
        $product = $this->getById($productId);

        if (empty($product)) {
            return null;
        }

        $orderItems = $this->orderItemRepository->getByProductId($productId);

        if (count($orderItems) > 0) {
            return 0;
        }

        $discounts = $this->discountCriteriaRepository->getByProductId($productId);
        if (count($discounts) > 0) {
            return -1;
        }

        return $this->productRepository->delete($productId);
    }

    /** Return an array with paginated products and total number of results
     * @param int $page
     * @param int $limit
     * @param string $orderByAndDirection
     * @return array
     */
    public function getAllProducts($page = 1, $limit = 10, $orderByAndDirection = '-created_on')
    {
        $userId = (request()->user()) ? request()->user()->id : null;
        self::$pullInactiveProducts = $this->userAccessService->userHasAccess($userId, 'admin');

        if ($limit == 'null') {
            $limit = -1;
        }

        $orderByDirection = substr($orderByAndDirection, 0, 1) !== '-' ? 'asc' : 'desc';

        $orderByColumn = trim($orderByAndDirection, '-');

        $this->productRepository->setData($page, $limit, $orderByDirection, $orderByColumn);

        return [
            'results' => $this->productRepository->getProductsByConditions([]),
            'total_results' => $this->productRepository->countProducts()
        ];
    }

    /** Call the method that upload a file, from remotestorage package
     * @param string $target
     * @param file $file
     * @return bool
     */
    public function uploadThumbnailToRemoteStorage($target, $file)
    {
        return $this->remoteStorageService->put($target, $file);
    }

    /** Call the method that return the file url
     * @param string $target
     * @return mixed
     */
    public function url($target)
    {
        return $this->remoteStorageService->url($target);
    }
}