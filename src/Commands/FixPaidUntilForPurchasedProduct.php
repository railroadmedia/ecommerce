<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;

class FixPaidUntilForPurchasedProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "ecommerce:fix-paid-until {product_id} {--execute} {--silent}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Updates the paidUntil value of active subscriptions when the user also purchased the specified product";

    protected SubscriptionRepository $subscriptionRepository;
    protected EcommerceEntityManager $ecommerceEntityManager;
    protected ProductRepository $productRepository;
    protected UserProductRepository $userProductRepository;

    protected bool $simulate = false;
    protected bool $verbose = false;

    /**
     * Execute the console command.
     *
     * @param SubscriptionRepository $subscriptionRepository
     * @param EcommerceEntityManager $ecommerceEntityManager
     * @param ProductRepository $productRepository
     * @param UserProductRepository $userProductRepository
     *
     * @return int
     * @throws ORMException
     */
    public function handle(SubscriptionRepository $subscriptionRepository,
                           EcommerceEntityManager $ecommerceEntityManager,
                           ProductRepository      $productRepository,
                           UserProductRepository  $userProductRepository): int
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->ecommerceEntityManager = $ecommerceEntityManager;
        $this->productRepository = $productRepository;
        $this->userProductRepository = $userProductRepository;

        $this->simulate = $this->option("execute") == false;
        $this->verbose = !$this->option("silent");

        if ($this->simulate) {
            $this->info("Executing in simulation mode. No changes will be made to the database.  Use --execute to run for real.");
        }

        $productId = $this->argument("product_id");
        try {
            $product = $this->getProduct($productId);
        } catch (Exception) {
            return self::FAILURE;
        }

        $purchasersAndExpiration = $this->getUsersAndExpirationDate($product->getId());
        $this->info("{$purchasersAndExpiration->count()} users have purchased this product. Checking earlier expiry dates ...");

        $toUpdate = $this->getSubscriptionsToUpdate($purchasersAndExpiration);

        $this->updateSubscriptions($toUpdate);

        return self::SUCCESS;
    }

    /**
     * @param int $productId
     * @return Product
     *
     * @throws Exception
     */
    private function getProduct(int $productId): Product
    {
        // identify the product
        try {
            $product = $this->productRepository->findProduct($productId);
        } catch (NonUniqueResultException) {
            $this->error("Multiple products found for product id $productId");
            throw new Exception();
        }
        if (is_null($product)) {
            $this->error("No product found for product id $productId");
            throw new Exception();
        }

        // have the user confirm the product, unless they"ve used the --no-interaction flag
        if (!$this->option("no-interaction") &&
            !$this->confirm("Using product '{$product->getName()}'. Do you wish to continue?")) {
            $this->error("Exiting...");
            throw new Exception();
        } else {
            $this->info("Using product '{$product->getName()}'");
        }

        return $product;
    }

    /**
     * Get the Users who purchased this product and the expiration date of their purchase
     *
     * @param int $productId
     * @return Collection<PurchaserInformation>
     */
    private function getUsersAndExpirationDate(int $productId): Collection
    {
        $qb = $this->userProductRepository->createQueryBuilder("up");

        $qb->where(
            $qb->expr()
                ->eq("up.product", ":productId")
        )
            ->setParameter("productId", $productId);

        $results = $qb->getQuery()->getResult();

        return collect($results)->map(function (UserProduct $userProduct) {
            return new PurchaserInformation($userProduct->getUser(), $userProduct->getExpirationDate());
        });
    }

    /**
     * Go through the collection of purchasers and get the subscriptions that should be updated
     *
     * @param Collection<PurchaserInformation> $purchasersAndExpiration
     * @return Collection<SubscriptionToUpdate>
     */
    private function getSubscriptionsToUpdate(Collection $purchasersAndExpiration): Collection
    {
        $toUpdate = collect();
        $skipped = collect();
        $tableData = collect();

        $purchasersAndExpiration->each(function (PurchaserInformation $purchaserInformation) use ($tableData, $skipped, $toUpdate) {

            $userId = $purchaserInformation->user->getId();
            $email = $purchaserInformation->user->getEmail();
            $productExpirationDate = $purchaserInformation->expirationDate->toDateTimeString();

            // get the user's active subscription(s)
            $activeSubscriptions = $this->subscriptionRepository->getUserActiveSubscription($purchaserInformation->user);
            // safety step because getUserActiveSubscription() doesn't order or sort, so make sure the oldest is first
            $activeSubscriptions = collect($activeSubscriptions)->sortBy("created_at");

            if ($activeSubscriptions->isEmpty()) {
                $subscriptionProductName = "n/a";
                $subscriptionPaidUntil = "n/a";
                $note = "No active subscriptions";
                $needsUpdate = false;

                $skipped->push($purchaserInformation->user->getEmail());
            } else {
                $activeSubscription = $activeSubscriptions->first();

                $subscriptionProductName = $activeSubscription->getProduct()?->getName() ?? "(unknown)";
                $subscriptionPaidUntil = $activeSubscription->getPaidUntil()->toDateTimeString();

                if ($activeSubscription->getPaidUntil()->isBefore($purchaserInformation->expirationDate)) {
                    $note = null;
                    $needsUpdate = true;

                    $toUpdate->push(new SubscriptionToUpdate($activeSubscription, $purchaserInformation->expirationDate ));
                } else {
                    $note = "Active subscription already expires later";
                    $needsUpdate = false;

                    $skipped->push($purchaserInformation->user->getEmail());
                }
            }

            $tableData->push(new PurchaserTableData($userId, $email, $subscriptionProductName, $subscriptionPaidUntil, $productExpirationDate, $needsUpdate, $note));
        });

        if ($this->verbose) {
            $this->table([
                "User ID",
                "Email",
                "Subscription Product Name",
                "Subscription Paid Until",
                "Product Expiration Date",
                "Update?",
                "Notes"
            ],
            $tableData->map(function ($row) {
                return collect([
                        $row->userId,
                        $row->email,
                        $row->subscriptionProductName,
                        $row->subscriptionPaidUntil,
                        $row->productExpirationDate,
                        $row->needsUpdate ? "Yes" : "No",
                        $row->note,
                ]);
            }));

            $this->newLine();
        }
        $this->warn("Skipping {$skipped->count()} users.");
        $this->info("Going to update {$toUpdate->count()} subscriptions ...");

        return $toUpdate;
    }

    /**
     * @param Collection<SubscriptionToUpdate> $toUpdate
     * @return void
     * @throws ORMException
     */
    private function updateSubscriptions(Collection $toUpdate): void
    {
        $bar = $this->output->createProgressBar($toUpdate->count());
        $bar->start();

        $toUpdate->each(function (SubscriptionToUpdate $subscriptionToUpdate) use ($bar) {
            $subscription = $subscriptionToUpdate->subscription;
            $paidUntil = $subscriptionToUpdate->paidUntil;

            $oldSubscription = clone $subscription;
            $subscription->setPaidUntil($paidUntil);
            if (!$this->simulate) {
                $this->ecommerceEntityManager->persist($subscription);
                $this->ecommerceEntityManager->flush($subscription);
            }

            if ($this->verbose) {
                $this->info("Subscription ID {$oldSubscription->getId()} paidUntil updated from "
                    . "{$oldSubscription->getPaidUntil()->toDateTimeString()} to {$subscription->getPaidUntil()->toDateTimeString()}");
            }

            $bar->advance();
        });

        $bar->finish();
        $this->newLine();
    }
}

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */
final class PurchaserInformation {
    public function __construct(
        public User $user,
        public Carbon $expirationDate
    ) {}
}

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */
final class SubscriptionToUpdate {
    public function __construct(
        public Subscription $subscription,
        public Carbon $paidUntil
    ) {}
}

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */
final class PurchaserTableData {
    public function __construct(
        public int $userId,
        public string $email,
        public string $subscriptionProductName,
        public string $subscriptionPaidUntil,
        public string $productExpirationDate,
        public bool $needsUpdate,
        public string|null $note,
    ){}
}