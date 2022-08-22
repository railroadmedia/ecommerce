<?php

namespace Railroad\Ecommerce\Drivers;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Doctrine\DBAL\Driver\PDO\Connection;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Deprecations\Deprecation;
use PDO;

class ExistingPDOSqliteDriver extends AbstractSQLiteDriver
{
    /** @var mixed[] */
    protected $_userDefinedFunctions = [
        'sqrt' => ['callback' => [SqlitePlatform::class, 'udfSqrt'], 'numArgs' => 1],
        'mod'  => ['callback' => [SqlitePlatform::class, 'udfMod'], 'numArgs' => 2],
        'locate'  => ['callback' => [SqlitePlatform::class, 'udfLocate'], 'numArgs' => -1],
    ];

    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        if (isset($driverOptions['userDefinedFunctions'])) {
            $this->_userDefinedFunctions = array_merge(
                $this->_userDefinedFunctions,
                $driverOptions['userDefinedFunctions']
            );
            unset($driverOptions['userDefinedFunctions']);
        }

        try {
            if (isset($params['pdo'])) {
                $pdo = $params['pdo'];
                $params['existing_pdo'] = true;
                unset($params['pdo']);
            } else {
                $pdo = new PDO(
                    $this->_constructPdoDsn($params),
                    $username,
                    $password,
                    $driverOptions
                );
            }
        } catch (PDOException $ex) {
            throw Exception::driverException($this, $ex);
        }

        foreach ($this->_userDefinedFunctions as $fn => $data) {
            $pdo->sqliteCreateFunction($fn, $data['callback'], $data['numArgs']);
        }

        return new Connection($pdo);
    }

    /**
     * Constructs the Sqlite PDO DSN.
     *
     * @param mixed[] $params
     *
     * @return string The DSN.
     */
    protected function _constructPdoDsn(array $params)
    {
        $dsn = 'sqlite:';
        if (isset($params['path'])) {
            $dsn .= $params['path'];
        } elseif (isset($params['memory'])) {
            $dsn .= ':memory:';
        }

        return $dsn;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    public function getName()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/3580',
            'Driver::getName() is deprecated'
        );

        return 'pdo_sqlite';
    }
}