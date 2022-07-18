<?php

namespace Railroad\Ecommerce\Commands;

use DateTime;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Carbon\Carbon;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Listeners\OrderOneTimeProductListener;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\DateTimeService;
use Railroad\Ecommerce\Services\UserProductService;

class GetOneTimeProductSubscriptionIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GetOneTimeProductSubscriptionIssues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Returns a list of subscriptions that are renewing 6 or more days before one time product brand access expires';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;
    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;
    /**
     * @var EcommerceEntityManager
     */
    private $ecommerceEntityManager;
    /**
     * @var UserProductService
     */
    protected $userProductService;
    /**
     * @var DateTimeService
     */
    protected $dateTimeService;
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @param DatabaseManager $databaseManager
     * @param SubscriptionRepository $subscriptionRepository
     * @param EcommerceEntityManager $ecommerceEntityManager
     * @param UserProductService $userProductService
     * @param DateTimeService $dateTimeService
     * @param OrderRepository $orderRepository
     *
     */
    public function __construct(
        DatabaseManager        $databaseManager,
        SubscriptionRepository $subscriptionRepository,
        EcommerceEntityManager $ecommerceEntityManager,
        UserProductService     $userProductService,
        DateTimeService        $dateTimeService,
        OrderRepository        $orderRepository
    )
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->ecommerceEntityManager = $ecommerceEntityManager;
        $this->userProductService = $userProductService;
        $this->dateTimeService = $dateTimeService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $flaggedSubscriptions = collect($this->databaseManager->connection('musora_mysql')
            ->select("SELECT s.id, s.user_id, u.email, s.brand, s.start_date, s.paid_until, latest_expiration_date.access_until, latest_expiration_date.name, latest_expiration_date.product_id, o2.id as order_id
                            FROM ecommerce_subscriptions s
                            INNER JOIN usora_users u on u.id = s.user_id
                            INNER JOIN (SELECT up.user_id, p.brand, p.name, up.product_id, max(expiration_date) as access_until FROM ecommerce_user_products up
                                INNER JOIN ecommerce_products p on p.id = up.product_id
                                where expiration_date is not null and expiration_date >= CURRENT_TIME and digital_access_time_type = 'one time'
                                group by up.user_id, p.brand
                                order by up.user_id DESC) latest_expiration_date on latest_expiration_date.user_id = s.user_id and latest_expiration_date.brand = s.brand
                            INNER JOIN (SELECT oi.product_id, o.id, o.user_id from ecommerce_orders o 
                                       INNER JOIN  ecommerce_order_items oi on oi.order_id = o.id) o2 on o2.product_id = latest_expiration_date.product_id and o2.user_id = s.user_id
                            where s.is_active and s.paid_until >= CURRENT_TIME and s.canceled_on is null and latest_expiration_date.access_until > DATE_ADD(s.paid_until, INTERVAL 6 DAY) and s.type <> 'payment plan'
                            order by s.paid_until"));
        $this->info($this->description);
        $this->table(['User ID', 'Email', 'Brand', 'Product Name', 'Subscription Paid Until', 'Product Access Until', 'Diff Days'], $flaggedSubscriptions->map(function ($row) {
            $diffDays = (new DateTime($row->access_until))->diff(new DateTime($row->paid_until))->format('%a');
            return collect([$row->user_id, $row->email, $row->brand, $row->name, $row->paid_until, $row->access_until, $diffDays]);
        }));
        $this->info("{$flaggedSubscriptions->count()} records found");


        if ($this->confirm('Attempt to resolve issues automatically?')) {
            $listener = new OrderOneTimeProductListener($this->subscriptionRepository,
                $this->ecommerceEntityManager,
                $this->userProductService,
                $this->dateTimeService);

            $i = 1;
            foreach ($flaggedSubscriptions as $flaggedSubscription){
                $orderId = $flaggedSubscription->order_id;
                $this->info("<fg=white>{i}/{$flaggedSubscriptions->count()}</>");

                $order = $this->orderRepository->find($orderId);
                $listener->handle(new OrderEvent($order, null));

                $subscriptionID = $flaggedSubscription->id;
                $updates = collect($this->databaseManager->connection('musora_mysql')
                    ->select("SELECT s.id, s.paid_until, up.expiration_date
                            FROM ecommerce_subscriptions s
                            INNER JOIN ecommerce_products p on s.product_id = p.id
                            INNER JOIN ecommerce_user_products up on up.product_id = p.id and up.user_id = s.user_id
                            WHERE s.id = {$subscriptionID}
                            "))->first();

                $this->info("\nUser: {$flaggedSubscription->user_id}\nOne Time Product: {$flaggedSubscription->name}");
                $this->info("Subscription paid_until <fg=white>{$flaggedSubscription->paid_until}</> to <fg=white>{$updates->paid_until}</>");
                $this->info("Subscription expiration_date <fg=white>{$flaggedSubscription->access_until}</> to <fg=white>{$updates->expiration_date}</>");
                $i++;
            };
        }
    }
}
