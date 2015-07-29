<?php
namespace tests\Db;

class ConnectionTest extends DatabaseTestCase
{
    public function testQuotingNames()
    {
        $names  = ['columnA', 'columnB'];
        $result = ['`columnA`', '`columnB`'];

        $this->assertEquals('`columnA`', self::$conn->quoteName(reset($names)));
        $this->assertEquals($result, self::$conn->quoteNames($names));
    }

    public function testQuotingValues()
    {
        // scalar value
        $this->assertEquals(1,        self::$conn->quoteValue(1));
        $this->assertEquals(1.5,      self::$conn->quoteValue(1.5));
        $this->assertEquals("'name'", self::$conn->quoteValue('name'));

        // magic value
        $this->assertEquals("''", self::$conn->quoteValue(null));
        $this->assertEquals('1, 2, 3', self::$conn->quoteValue([1, 2, 3]));

        // array value
        $this->assertEquals([1, "'aaa'"], self::$conn->quoteValues([1, 'aaa']));

        // in string value
        $this->assertEquals('a = 1', self::$conn->quoteInString('a = ?', 1));

        $source = 'a = ? AND b > ? OR c IN(?)';
        $result = 'a = 1 AND b > 10 OR c IN(1, 2, 3)';
        $this->assertEquals($result, self::$conn->quoteInString($source, [1, 10, [1, 2, 3]]));
    }

    public function testQueryFactories()
    {
        $this->assertInstanceOf(\Graphite\Db\Query\Select::class, self::$conn->select());
        $this->assertInstanceOf(\Graphite\Db\Query\Insert::class, self::$conn->insert());
        $this->assertInstanceOf(\Graphite\Db\Query\Update::class, self::$conn->update());
        $this->assertInstanceOf(\Graphite\Db\Query\Delete::class, self::$conn->delete());
    }
}
