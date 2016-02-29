<?php
namespace tests\Graphite\Db;

use Graphite\Db\SqlParser;

class SqlParserTest extends \PHPUnit_Framework_TestCase
{

    public function testSplitQueries()
    {
        $sql = file_get_contents('./tests/data/parse.sql');

        $this->assertCount(3,             SqlParser::splitQueries($sql));
        $this->assertEquals(['select 1'], SqlParser::splitQueries('select 1'));
    }

    public function testParseTableName()
    {
        $this->assertFalse(SqlParser::parseTableName('SELECT users;'));
        $this->assertFalse(SqlParser::parseTableName('foobar'));

        $this->assertEquals('users', SqlParser::parseTableName('SELECT * FROM users;'));
        $this->assertEquals('users', SqlParser::parseTableName('SELECT * FROM `schema`.`users`;'));
        $this->assertEquals('users', SqlParser::parseTableName('UPDATE `users` SET age = 1;'));
        $this->assertEquals('users', SqlParser::parseTableName('insert ignore into users values (1,2)'));
        $this->assertEquals('users', SqlParser::parseTableName('DELETE FROM `users`'));
        $this->assertEquals('users', SqlParser::parseTableName('CREATE   TABLE if not exists users'));
    }
}
