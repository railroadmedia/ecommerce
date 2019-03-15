<?php

namespace Railroad\Ecommerce\Services;

use Doctrine\ORM\QueryBuilder;
use League\Fractal\Serializer\JsonApiSerializer;
use Railroad\Doctrine\Services\FractalResponseService;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Transformers\AccessCodeTransformer;
use Railroad\Ecommerce\Transformers\AddressTransformer;
use Railroad\Ecommerce\Transformers\CartItemTransformer;
use Railroad\Ecommerce\Transformers\DecoratedOrderTransformer;
use Railroad\Ecommerce\Transformers\DiscountCriteriaTransformer;
use Railroad\Ecommerce\Transformers\DiscountTransformer;
use Railroad\Ecommerce\Transformers\FulfillmentTransformer;
use Railroad\Ecommerce\Transformers\OrderTransformer;
use Railroad\Ecommerce\Transformers\PaymentTransformer;
use Railroad\Ecommerce\Transformers\PaymentMethodTransformer;
use Railroad\Ecommerce\Transformers\ProductTransformer;
use Railroad\Ecommerce\Transformers\RefundTransformer;
use Railroad\Ecommerce\Transformers\ShippingCostsWeightRangeTransformer;
use Railroad\Ecommerce\Transformers\ShippingOptionTransformer;
use Railroad\Ecommerce\Transformers\SubscriptionTransformer;
use Railroad\Ecommerce\Transformers\UserPaymentMethodsTransformer;
use Spatie\Fractal\Fractal;

class ResponseService extends FractalResponseService
{
    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     * @return Fractal
     */
    public static function address($entityOrEntities, QueryBuilder $queryBuilder = null, array $includes = [])
    {
        return self::create($entityOrEntities, 'address', new AddressTransformer(), new JsonApiSerializer(), $queryBuilder)
            ->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     * @return Fractal
     */
    public static function accessCode(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'accessCode',
                new AccessCodeTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param AccessCode|array $accessCodes
     * @param array $products - array of Products
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function decoratedAccessCode(
        $accessCodes,
        $products,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $accessCodes,
                'accessCode',
                new AccessCodeTransformer($products),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function product(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'product',
                new ProductTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param string $url
     *
     * @return Fractal
     */
    public static function productThumbnail(string $url)
    {
        return fractal(
                null,
                function() {
                    return null;
                },
                new JsonApiSerializer()
            )->addMeta(['url' => $url]);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function discount(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'discount',
                new DiscountTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function discountCriteria(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'discountCriteria',
                new DiscountCriteriaTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function shippingOption(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'shippingOption',
                new ShippingOptionTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function shippingCost(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'shippingCostsWeightRange',
                new ShippingCostsWeightRangeTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function subscription(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'subscription',
                new SubscriptionTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function paymentMethod(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'paymentMethod',
                new PaymentMethodTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $userPaymentMethods
     * @param array $creditCards
     * @param array $paypalAgreements
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function userPaymentMethods(
        $userPaymentMethods,
        $creditCards,
        $paypalAgreements,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $userPaymentMethods,
                'userPaymentMethods',
                new UserPaymentMethodsTransformer(
                    $creditCards,
                    $paypalAgreements
                ),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function payment(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'payment',
                new PaymentTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function refund(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'refund',
                new RefundTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function fulfillment(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'fulfillment',
                new FulfillmentTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function order(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'order',
                new OrderTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param Order $order
     * @param array $payments - array of Payments
     * @param array $refunds - array of Refunds
     * @param array $subscriptions - array of Subscriptions
     * @param array $paymentPlans - array of PaymentPlans
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function decoratedOrder(
        Order $order,
        array $payments = [],
        array $refunds = [],
        array $subscriptions = [],
        array $paymentPlans = [],
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {

        $transformer = new DecoratedOrderTransformer(
            $payments,
            $refunds,
            $subscriptions,
            $paymentPlans
        );

        return self::create(
                $order,
                'order',
                $transformer,
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param string $url
     *
     * @return Fractal
     */
    public static function redirect(string $url)
    {
        return fractal(
                null,
                function() {
                    return null;
                },
                new JsonApiSerializer()
            )->addMeta(['redirect' => $url]);
    }

    /**
     * @param array $cartItems
     * @param Address $billingAddress
     * @param Address $shippingAddress
     * @param array $paymentPlansPricing
     * @param float $totalDue
     *
     * @return Fractal
     */
    public static function orderForm(
        array $cartItems,
        ?Address $billingAddress,
        ?Address $shippingAddress,
        array $paymentPlansPricing,
        float $totalDue
    ) {

        /*
        billingAddress & shippingAddress are exported using meta key to avoid
        InvalidArgumentException: JSON API resource objects MUST have a valid id
        */

        return self::create(
                $cartItems,
                'cartItem',
                new CartItemTransformer(),
                new JsonApiSerializer()
            )
            ->addMeta([
                'paymentPlansPricing' => $paymentPlansPricing,
                'totalDue' => $totalDue,
                'billingAddress' => $billingAddress ? $billingAddress->toArray() : null,
                'shippingAddress' => $shippingAddress ? $shippingAddress->toArray() : null,
            ]);
    }

    /**
     * @param Address $billingAddress
     * @param Address $shippingAddress
     *
     * @return Fractal
     */
    public static function sessionAddresses(
        ?Address $billingAddress,
        ?Address $shippingAddress
    ) {
        return fractal(
                null,
                function() {
                    return null;
                },
                new JsonApiSerializer()
            )->addMeta([
                'billingAddress' => $billingAddress->toArray(),
                'shippingAddress' => $shippingAddress->toArray(),
            ]);
    }

    /**
     * @param array $cartItems
     * @param array $metaData
     *
     * @return Fractal
     */
    public static function cartData(
        array $cartItems,
        array $metaData
    ) {
        return self::create(
                $cartItems,
                'cartItem',
                new CartItemTransformer(),
                new JsonApiSerializer()
            )
            ->addMeta($metaData);
    }
}
