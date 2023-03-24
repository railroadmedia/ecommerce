<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;

class GetCustomersWithWrongfullyChargedWithQST extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GetCustomersWithWrongfullyChargedWithQST';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Returns a list users that have been charged with QST tax before April 1st 2023 on Guitareo/Singeo/Pianote';

    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
    }

    public function handle()
    {
        $orderPayments = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->select("SELECT uu.id as user_id, uu.email as user_email, ec.email as customer_email, 
                ec.id as customer_id, eop.payment_id, ep.type as payment_type, 
                eo.brand, ep.created_at, eo.id as order_id
                FROM musora_laravel.ecommerce_payment_taxes ept
                join musora_laravel.ecommerce_payments ep on ept.payment_id = ep.id
                join  musora_laravel.ecommerce_order_payments  eop on ept.payment_id = eop.payment_id
                join  musora_laravel.ecommerce_orders eo on eo.id = eop.order_id
                left join musora_laravel.usora_users uu on uu.id = eo.user_id
                left join musora_laravel.ecommerce_customers ec on eo.customer_id = ec.id
                where ept.region = 'Quebec'
                and eo.brand != 'drumeo'
                and ep.created_at > '2022-01-01 00:00:00'
                and ept.product_rate = 0.15
                order by ep.created_at DESC;"
            );




        $subscriptionPayments = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->select("SELECT uu.id as user_id, uu.email as user_email, ec.email as customer_email,
                ec.id as customer_id, ep.id as payment_id, ep.type as payment_type, 
                es.brand, ep.created_at, es.id as subscription_id
                FROM musora_laravel.ecommerce_payment_taxes ept
                join musora_laravel.ecommerce_payments ep on ept.payment_id = ep.id
                join musora_laravel.ecommerce_subscription_payments esp on esp.payment_id = ep.id
                join musora_laravel.ecommerce_subscriptions es on esp.subscription_id = es.id
                left join musora_laravel.usora_users uu on uu.id = es.user_id
                left join musora_laravel.ecommerce_customers ec on es.customer_id = ec.id
                left join musora_laravel.ecommerce_order_payments eop on eop.payment_id = ep.id
                where ept.region = 'Quebec'
                and es.brand != 'drumeo'
                and ep.created_at > '2022-01-01 00:00:00'
                and ept.product_rate = 0.15
                and eop.order_id is NULL
                order by ep.created_at DESC;"
            )
        ;

        $payments = array_merge($subscriptionPayments, $orderPayments);

        $arrayPayments = [];

        foreach ($payments as $payment) {
            $arrayPayments[] = [
                $payment->payment_id,
                $payment->payment_type,
                $payment->created_at,
                $payment->brand,
                $payment->user_email ?? $payment->customer_email,
                $payment->user_id,
                "https://admin.musora.com/invoice/" . $payment->payment_id,
                $payment->customer_id
            ];
        }

        $f = fopen('qst_payments.csv', "w");

        fputcsv(
            $f,
            [
                'Payment ID',
                'Payment Type',
                'Payment Date',
                'Brand',
                'User/Customer Email',
                'User Id',
                'Invoice Link',
                'Customer Id'
            ]
        );


        foreach ($arrayPayments as $line) {
            fputcsv($f, $line);
        }

        fclose($f);

    }


}