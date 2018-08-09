<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;


class StatsController extends BaseController
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * StatsController constructor.
     *
     * @param ProductRepository $productRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        OrderRepository $orderRepository
    ) {
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
    }

    public function statsProduct(Request $request)
    {
        $products =
            $this->productRepository->query()
                ->where(
                    ConfigService::$tableProduct . '.brand',
                    $request->get('brand', ConfigService::$brand)
                )
                ->get();
        foreach ($products as $index => $product) {
            //get info from orders
            $orders =
                $this->orderRepository->query()
                    ->join(
                        ConfigService::$tableOrderItem,
                        ConfigService::$tableOrder . '.id',
                        '=',
                        ConfigService::$tableOrderItem . '.order_id'
                    )
                    ->where(
                        ConfigService::$tableOrder . '.created_on',
                        '>',
                        Carbon::parse($request->get('start-date', Carbon::now()))
                            ->startOfDay()
                    )
                    ->where(
                        ConfigService::$tableOrder . '.created_on',
                        '<',
                        Carbon::parse($request->get('end-date', Carbon::now()))
                            ->endOfDay()
                    )
                    ->where(ConfigService::$tableOrderItem . '.product_id', $product->id)
                    ->get();
            $products[$index]['quantity'] = $orders->sum('quantity');
            $products[$index]['paid'] = $orders->sum('total_price');
            $products[$index]['tax'] = $orders->sum('total_price') / $orders->sum('paid') * $orders->sum('tax');

            //get info from subscriptions/payment plans
        }

        return reply()->json($products);
    }
}