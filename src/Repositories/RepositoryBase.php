<?php
namespace Railroad\Ecommerce\Repositories;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;

abstract class RepositoryBase
{
    /**
     * @var DatabaseManager
     */
    protected $databaseManager;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Connection
     */
    public static $connectionMask;

    /**
     * RepositoryBase constructor.
     */
    public function __construct()
    {
        $this->databaseManager = app('db');

        if (empty(self::$connectionMask)) {
            /**
             * @var $realConnection Connection
             */
            $realConnection = app('db')->connection(ConfigService::$databaseConnectionName);
            $realConfig = $realConnection->getConfig();

            $realConfig['name'] = ConfigService::$connectionMaskPrefix . $realConfig['name'];

            $maskConnection =
                new Connection(
                    $realConnection->getPdo(),
                    $realConnection->getDatabaseName(),
                    $realConnection->getTablePrefix(),
                    $realConfig
                );

            if (!empty($realConnection->getSchemaGrammar())) {
                $maskConnection->setSchemaGrammar($realConnection->getSchemaGrammar());
            }

            $maskConnection->setQueryGrammar($realConnection->getQueryGrammar());
            $maskConnection->setEventDispatcher($realConnection->getEventDispatcher());
            $maskConnection->setPostProcessor($realConnection->getPostProcessor());

            self::$connectionMask = $maskConnection;
        }

        $this->connection = self::$connectionMask;
    }

    /**
     * @return Builder
     */
    protected abstract function query();

    /**
     * @return Connection
     */
    protected function connection()
    {
        return $this->connection;
    }

}