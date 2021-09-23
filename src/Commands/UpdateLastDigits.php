<?php

namespace Railroad\Ecommerce\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateLastDigits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateLastDigits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update credit card last four digits to have 0 char front padding for 4 chars total';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * UpdateLastDigits constructor.
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
        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_credit_cards')
            ->where(DB::raw('LENGTH(last_four_digits)'), 1)
            ->update(['last_four_digits' => DB::raw("CONCAT('000', last_four_digits)")]);

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_credit_cards')
            ->where(DB::raw('LENGTH(last_four_digits)'), 2)
            ->update(['last_four_digits' => DB::raw("CONCAT('00', last_four_digits)")]);

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_credit_cards')
            ->where(DB::raw('LENGTH(last_four_digits)'), 3)
            ->update(['last_four_digits' => DB::raw("CONCAT('0', last_four_digits)")]);
    }
}