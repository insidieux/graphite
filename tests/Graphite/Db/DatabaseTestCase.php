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
        
        $sql = file_get_contents(__DIR__ . '/../../data/database.sql');
        $sql = explode(';', $sql);

        // create test schemas and data
        $pdo = new \PDO("mysql:host={$GLOBALS['DB_HOST']};dbname={$GLOBALS['DB_DBNAME']}", $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);

        foreach ($sql as $query) {
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }
    }
}
