<?php
namespace tests\Db;

use Graphite\Db\Expr;
use Graphite\Db\Query\Delete;
use Graphite\Db\Query\Insert;
use Graphite\Db\Query\Select;
use Graphite\Db\Query\Update;

class ConnectionTest extends DatabaseTestCase
{
    public function testQuoteName()
    {
        $this->assertEquals('`col`',       self::$conn->quoteName('col'));
        $this->assertEquals('`tbl`.`col`', self::$conn->quoteName('tbl.col'));
        $this->assertEquals('COUNT(*)',    self::$conn->quoteName(new Expr('COUNT(*)')));
        $this->assertEquals('*',           self::$conn->quoteName('*'));
    }

    public function testQuoteNames()
    {
        $this->assertEquals(['`col1`', '`col2`'], self::$conn->quoteNames(['col1', 'col2']));
    }

    public function testQuoteValue()
    {
        $this->assertEquals(1,            self::$conn->quoteValue(1));
        $this->assertEquals(1.5,          self::$conn->quoteValue(1.5));
        $this->assertEquals("'name'",     self::$conn->quoteValue('name'));
        $this->assertEquals("''",         self::$conn->quoteValue(null));
        $this->assertEquals('1, 2, 3',    self::$conn->quoteValue([1, 2, 3]));
        $this->assertEquals([1, "'aaa'"], self::$conn->quoteValues([1, 'aaa']));
    }

    public function testQuoteValues()
    {
        $this->assertEquals([1, "'foo'"], self::$conn->quoteValues([1, 'foo']));
    }

    public function testQuoteInString()
    {
        $expected = 'a = 1 AND b > 10 OR c IN(1, 2, 3)';
        $actual   = self::$conn->quoteInString('a = ? AND b > ? OR c IN(?)', [1, 10, [1, 2, 3]]);
        $this->assertEquals($expected, $actual);
    }

    public function testQuery()
    {

    }

    public function testExec()
    {

    }

    public function testGetPdoInstance()
    {

    }

    public function testQueryFactories()
    {
        $this->assertInstanceOf(Select::class, self::$conn->select());
        $this->assertInstanceOf(Insert::class, self::$conn->insert());
        $this->assertInstanceOf(Update::class, self::$conn->update());
        $this->assertInstanceOf(Delete::class, self::$conn->delete());
    }

    public function testExprFactory()
    {
        $expr = self::$conn->expr('COUNT(*)');

        $this->assertInstanceOf(Expr::class, $expr);
        $this->assertEquals('COUNT(*)', $expr->get());
    }
}
