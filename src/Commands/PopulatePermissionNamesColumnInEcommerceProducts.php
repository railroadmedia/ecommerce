<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

class PopulatePermissionNamesColumnInEcommerceProducts extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PopulatePermissionNamesColumnInEcommerceProducts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate new column from ecommerce_products table, digital_access_permission_names, with the permission names saved in config files of each brand.';

    public function __construct(
        DatabaseManager $databaseManager
    )
    {
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
        $start = microtime(true);
        $permissionsNamesPerSku = [];
        $nrOfUpdatedProducts = 0;

        foreach (config('event-data-synchronizer.ecommerce_product_sku_to_content_permission_name_map') as $key => $item) {
            $permissionsNamesPerSku[$key] = $item;
        }

        $ecommerceProducts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_products')
            ->whereIn('sku', array_keys($permissionsNamesPerSku))
            ->get()
            ->toArray();

        foreach ($ecommerceProducts as $ecommerceProduct) {
            if (array_key_exists($ecommerceProduct->sku, $permissionsNamesPerSku)) {
                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_products')
                    ->where('sku', $ecommerceProduct->sku)
                    ->update(['digital_access_permission_names' => '["' . $permissionsNamesPerSku[$ecommerceProduct->sku] . '"]']);
                $nrOfUpdatedProducts++;
            }
        }

        $finish = microtime(true) - $start;
        $format = "Finished updating digital access permission names columns in ecommerce_products table in total %s seconds.\nTotal number of updated products: %s.\n";
        $this->info(sprintf($format, $finish, $nrOfUpdatedProducts));

    }

}
