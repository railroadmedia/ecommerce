<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Structures\AccountingProduct;
use Railroad\Ecommerce\Entities\Structures\AccountingProductTotals;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;
use Throwable;

class AccountingService
{
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var RefundRepository
     */
    private $refundRepository;

    /**
     * AccountingService constructor.
     *
     * @param PaymentRepository $paymentRepository
     * @param RefundRepository $refundRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        RefundRepository $refundRepository
    )
    {
        $this->paymentRepository = $paymentRepository;
        $this->refundRepository = $refundRepository;
    }

    /**
     * @param Request $request
     *
     * @return AccountingProductTotals
     *
     * @throws Throwable
     */
    public function indexByRequest(Request $request): AccountingProductTotals
    {
        $smallDate = $request->get(
            'small_date_time',
            Carbon::now()
                ->subDay()
                ->startOfDay()
                ->toDateTimeString()
        );

        $smallDateTime =
            Carbon::parse($smallDate)
                ->startOfDay();

        $bigDate = $request->get(
            'big_date_time',
            Carbon::now()
                ->subDay()
                ->endOfDay()
                ->toDateTimeString()
        );

        $brand = $request->get('brand');

        $bigDateTime =
            Carbon::parse($bigDate)
                ->endOfDay();

        $result = new AccountingProductTotals($smallDate, $bigDate);

        $totalTax = $this->paymentRepository->getPaymentsTaxPaid($smallDateTime, $bigDateTime, $brand);
        $totalTax = $totalTax ? round($totalTax, 2) : 0;

        $result->setTaxPaid($totalTax);

        $totalShipping = $this->paymentRepository->getPaymentsShippingPaid($smallDateTime, $bigDateTime, $brand);
        $totalShipping = $totalShipping ? round($totalShipping, 2) : 0;

        $result->setShippingPaid($totalShipping);

        $totalFinance = $this->paymentRepository->getPaymentsFinancePaid($smallDateTime, $bigDateTime, $brand);
        $totalFinance = $totalFinance ? round($totalFinance, 2) : 0;

        $result->setFinancePaid($totalFinance);

        $totalRefund = $this->refundRepository->getRefundPaid($smallDateTime, $bigDateTime, $brand);
        $totalRefund = $totalRefund ? round($totalRefund, 2) : 0;

        $result->setRefunded($totalRefund);

        $netProduct = $this->paymentRepository->getPaymentsNetProduct($smallDateTime, $bigDateTime, $brand);
        $netProduct = $netProduct ? round($netProduct, 2) : 0;

        $result->setNetProduct($netProduct);

        $netPaid = $this->paymentRepository->getPaymentsNetPaid($smallDateTime, $bigDateTime, $brand);
        $netPaid = $netPaid ? round($netPaid, 2) : 0;

        $result->setNetPaid($netPaid);

        return $result;
    }
}
