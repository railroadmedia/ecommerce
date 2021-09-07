<?php

namespace Railroad\Ecommerce\ExternalHelpers;

use Stripe\AlipayAccount;
use Stripe\ApiRequestor;
use Stripe\ApplicationFee;
use Stripe\ApplicationFeeRefund;
// use Stripe\AttachedObject;
use Stripe\Balance;
use Stripe\BalanceTransaction;
use Stripe\BankAccount;
use Stripe\BitcoinReceiver;
use Stripe\BitcoinTransaction;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Collection;
use Stripe\CountrySpec;
use Stripe\Coupon;
use Stripe\Customer;
use Stripe\Dispute;
use Stripe\Event;
// use Stripe\FileUpload;
use Stripe\Invoice;
use Stripe\InvoiceItem;
use Stripe\Order;
use Stripe\OrderReturn;
use Stripe\Plan;
use Stripe\Product;
use Stripe\Recipient;
use Stripe\Refund;
use Stripe\SKU;
use Stripe\Stripe as StripeMain;
use Stripe\StripeObject;
use Stripe\Subscription;
use Stripe\Token;
use Stripe\Transfer;
use Stripe\TransferReversal;

class StripeDependencies
{
    /**
     * @var AlipayAccount
     */
    // public $alipayAccount;
    /**
     * @var ApiRequestor
     */
    public $apiRequestor;
    /**
     * @var ApplicationFee
     */
    public $applicationFee;
    /**
     * @var ApplicationFeeRefund
     */
    public $applicationFeeRefund;
    /**
     * @var AttachedObject
     */
    // public $attachedObject;
    /**
     * @var Balance
     */
    public $balance;
    /**
     * @var BalanceTransaction
     */
    public $balanceTransaction;
    /**
     * @var BankAccount
     */
    public $bankAccount;
    /**
     * @var BitcoinReceiver
     */
    public $bitcoinReceiver;
    /**
     * @var BitcoinTransaction
     */
    public $bitcoinTransaction;
    /**
     * @var Card
     */
    public $card;
    /**
     * @var Charge
     */
    public $charge;
    /**
     * @var Collection
     */
    public $collection;
    /**
     * @var CountrySpec
     */
    public $countrySpec;
    /**
     * @var Coupon
     */
    public $coupon;
    /**
     * @var Customer
     */
    public $customer;
    /**
     * @var Dispute
     */
    public $dispute;
    /**
     * @var Event
     */
    public $event;
    /**
     * @var FileUpload
     */
    // public $fileUpload;
    /**
     * @var Invoice
     */
    public $invoice;
    /**
     * @var InvoiceItem
     */
    public $invoiceItem;
    /**
     * @var Order
     */
    public $order;
    /**
     * @var OrderReturn
     */
    public $orderReturn;
    /**
     * @var Plan
     */
    public $plan;
    /**
     * @var Product
     */
    public $product;
    /**
     * @var Recipient
     */
    public $recipient;
    /**
     * @var Refund
     */
    public $refund;
    /**
     * @var SKU
     */
    public $sku;
    /**
     * @var StripeMain
     */
    public $stripe;
    /**
     * @var StripeObject
     */
    public $stripeObject;
    /**
     * @var Subscription
     */
    public $subscription;
    /**
     * @var Token
     */
    public $token;
    /**
     * @var Transfer
     */
    public $transfer;
    /**
     * @var TransferReversal
     */
    public $transferReversal;

    public function __construct(
        AlipayAccount $alipayAccount,
        ApiRequestor $apiRequestor,
        ApplicationFee $applicationFee,
        ApplicationFeeRefund $applicationFeeRefund,
        // AttachedObject $attachedObject,
        Balance $balance,
        BalanceTransaction $balanceTransaction,
        BankAccount $bankAccount,
        BitcoinReceiver $bitcoinReceiver,
        BitcoinTransaction $bitcoinTransaction,
        Card $card,
        Charge $charge,
        Collection $collection,
        CountrySpec $countrySpec,
        Coupon $coupon,
        Customer $customer,
        Dispute $dispute,
        Event $event,
        // FileUpload $fileUpload,
        Invoice $invoice,
        InvoiceItem $invoiceItem,
        Order $order,
        OrderReturn $orderReturn,
        Plan $plan,
        Product $product,
        Recipient $recipient,
        Refund $refund,
        SKU $sku,
        StripeMain $stripe,
        StripeObject $stripeObject,
        Subscription $subscription,
        Token $token,
        Transfer $transfer,
        TransferReversal $transferReversal
    )
    {
        // $this->alipayAccount = $alipayAccount;
        $this->apiRequestor = $apiRequestor;
        $this->applicationFee = $applicationFee;
        $this->applicationFeeRefund = $applicationFeeRefund;
        // $this->attachedObject = $attachedObject;
        $this->balance = $balance;
        $this->balanceTransaction = $balanceTransaction;
        $this->bankAccount = $bankAccount;
        $this->bitcoinReceiver = $bitcoinReceiver;
        $this->bitcoinTransaction = $bitcoinTransaction;
        $this->card = $card;
        $this->charge = $charge;
        $this->collection = $collection;
        $this->countrySpec = $countrySpec;
        $this->coupon = $coupon;
        $this->customer = $customer;
        $this->dispute = $dispute;
        $this->event = $event;
        // $this->fileUpload = $fileUpload;
        $this->invoice = $invoice;
        $this->invoiceItem = $invoiceItem;
        $this->order = $order;
        $this->orderReturn = $orderReturn;
        $this->plan = $plan;
        $this->product = $product;
        $this->recipient = $recipient;
        $this->refund = $refund;
        $this->sku = $sku;
        $this->stripe = $stripe;
        $this->stripeObject = $stripeObject;
        $this->subscription = $subscription;
        $this->token = $token;
        $this->transfer = $transfer;
        $this->transferReversal = $transferReversal;
    }
}