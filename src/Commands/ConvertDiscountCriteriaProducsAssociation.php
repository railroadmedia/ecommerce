<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Throwable;

class ConvertDiscountCriteriaProducsAssociation extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ConvertDiscountCriteriaProducsAssociation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take values in the discount_criteria table product_id column and create ' .
    'a new record in ecommerce_discount_criterias_products table';

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(DatabaseManager $databaseManager
    ) {
        $this->info('Starting ConvertDiscountCriteriaProducsAssociation.');

        $done = 0;

        $databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_discount_criteria')
            ->orderBy('id', 'desc')
            ->chunk(
                500,
                function (Collection $rows) use (&$done) {
                    $insertData = [];

                    foreach ($rows as $item) {
                        // cast to array
                        $itemData = get_object_vars($item);

                        $insertData[] = [
                            'discount_criteria_id' => $itemData['id'],
                            'product_id' => $itemData['product_id'],
                        ];
                    }

                    $databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_discount_criterias_products')
                        ->insert($insertData);

                    $this->info('Done: ' . ++$done);
                }
            );

        $this->info('Finished ConvertDiscountCriteriaProducsAssociation.');
    }
}
