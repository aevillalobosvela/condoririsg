<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations
     * and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to
     * use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     */
    public $default = [
        'DSN'      => '',
        'hostname' => 'postgres-dev',
        'username' => 'devadmin',
        'password' => 'devpass',
        'database' => 'devdb',
        'DBDriver' => 'Postgre',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => false,
        'charset'  => 'UTF8',
        'schema'   => 'condoriri',
        'DBCollat' => '',
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port'     => 5432,
    ];
    /**
     * This database connection is used when
     * running PHPUnit database tests.
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => 'utf8_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
    ];

    public function __construct()
    {
        parent::__construct();

        // Override with environment variables if available
        if (isset($_ENV['database.default.hostname'])) {
            $this->default['hostname'] = $_ENV['database.default.hostname'];
        }
        if (isset($_ENV['database.default.username'])) {
            $this->default['username'] = $_ENV['database.default.username'];
        }
        if (isset($_ENV['database.default.password'])) {
            $this->default['password'] = $_ENV['database.default.password'];
        }
        if (isset($_ENV['database.default.database'])) {
            $this->default['database'] = $_ENV['database.default.database'];
        }
        if (isset($_ENV['database.default.DBDriver'])) {
            $this->default['DBDriver'] = $_ENV['database.default.DBDriver'];
        }
        if (isset($_ENV['database.default.port'])) {
            $this->default['port'] = (int)$_ENV['database.default.port'];
        }
        if (isset($_ENV['database.default.charset'])) {
            $this->default['charset'] = $_ENV['database.default.charset'];
        }
        if (isset($_ENV['database.default.schema'])) {
            $this->default['schema'] = $_ENV['database.default.schema'];
        }

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
