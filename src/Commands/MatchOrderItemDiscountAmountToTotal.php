<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class MatchOrderItemDiscountAmountToTotal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'MatchOrderItemDiscountAmountToTotal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This makes sure that the total discount amount for an order item is not greater that its total cost. This issue was initially caused by a bug which has since been fixed.';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * MatchOrderItemDiscountAmountToTotal constructor.
     *
     * @param DatabaseManager $databaseManager
     */
    public function __construct(
        DatabaseManager $databaseManager
    ) {
        parent::__construct();

        $this->databaseManager = $databaseManager;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $this->info("Started MatchOrderItemDiscountAmountToTotal command");

        $totalProcessed = 0;

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_order_items')
            ->whereRaw("(initial_price*quantity) - total_discounted < 0")
            ->orderBy('id', 'asc')
            ->chunkById(5000, function (Collection $rows) use (&$totalProcessed) {
                foreach ($rows as $row) {
                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_order_items')
                        ->where('id', $row->id)
                        ->update(['total_discounted' => $row->initial_price * $row->quantity]);

                    $totalProcessed++;
                }

                $this->info('processed: ' . $totalProcessed);
            });

        $this->info('processed: ' . $totalProcessed);

        $this->info("Finished MatchOrderItemDiscountAmountToTotal command");
    }
}