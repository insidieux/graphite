<?php
namespace tests\Graphite\Db;

use Graphite\Db\Connection;

abstract class DatabaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    protected static $conn;

    public static function setUpBeforeClass()
    {
        self::$conn = new Connection([
            'db_host'  => $GLOBALS['DB_HOST'],
            'db_name'  => $GLOBALS['DB_DBNAME'],
            'username' => $GLOBALS['DB_USER'],
            'password' => $GLOBALS['DB_PASSWD'],
        ]);
    }
}
