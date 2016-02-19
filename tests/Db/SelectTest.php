<?php
namespace tests\Db;

use Graphite\Db\Expr;
use Graphite\Db\Query\Select;

class SelectTest extends DatabaseTestCase
{
    protected function getSelect()
    {
        return new Select(self::$conn);
    }

    public function testColumnsWildcard()
    {
        $expected = 'SELECT * FROM `table`';
        $actual = $this->getSelect()->from('table')->toString();
        $this->assertEquals($expected, $actual);

        $actual = $this->getSelect()->from('table')->columns('*')->toString();
        $this->assertEquals($expected, $actual);

        $actual = $this->getSelect()->from('table')->columns(['*'])->toString();
        $this->assertEquals($expected, $actual);
    }

    public function testColumnsString()
    {
        $expected = 'SELECT `a` FROM `table`';
        $actual = $this->getSelect()->from('table')->columns('a')->toString();
        $this->assertEquals($expected, $actual);
    }

    public function testColumnsArray()
    {
        $expected = 'SELECT `a`, `b` FROM `table`';
        $actual = $this->getSelect()->from('table')->columns(['a', 'b'])->toString();
        $this->assertEquals($expected, $actual);
    }

    public function testColumnsAliases()
    {
        $expected = 'SELECT `a` AS `A`, `b` AS `B`, `c` FROM `table`';
        $actual = $this->getSelect()->from('table')->columns(['a' => 'A', 'b' => 'B', 'c'])->toString();
        $this->assertEquals($expected, $actual);
    }

    public function testColumnsExpr()
    {
        $expr = new Expr('COUNT(*)');

        $expected = 'SELECT COUNT(*) FROM `table`';
        $actual = $this->getSelect()->from('table')->columns($expr)->toString();
        $this->assertEquals($expected, $actual);

        $expected = 'SELECT `a`, COUNT(*) FROM `table`';
        $actual = $this->getSelect()->from('table')->columns(['a', $expr])->toString();
        $this->assertEquals($expected, $actual);
    }

    public function testGroupBy()
    {
        $expected = 'SELECT * FROM `table` GROUP BY `a`';
        $actual = $this->getSelect()->from('table')->groupBy('a')->toString();
        $this->assertEquals($expected, $actual);

        $expected = 'SELECT * FROM `table` GROUP BY `a`, `b`';
        $actual = $this->getSelect()->from('table')->groupBy(['a', 'b'])->toString();
        $this->assertEquals($expected, $actual);
    }

    public function testLimit()
    {
        $expected = 'SELECT * FROM `table` LIMIT 1';
        $actual = $this->getSelect()->from('table')->limit(1)->toString();
        $this->assertEquals($expected, $actual);

        $expected = 'SELECT * FROM `table` LIMIT 10, 1';
        $actual = $this->getSelect()->from('table')->limit(1)->offset(10)->toString();
        $this->assertEquals($expected, $actual);
    }

    public function testOrderBy()
    {
        $expected = 'SELECT * FROM `table` ORDER BY `a` ASC';
        $actual = $this->getSelect()->from('table')->orderBy('a')->toString();
        $this->assertEquals($expected, $actual);

        $expected = 'SELECT * FROM `table` ORDER BY `a` ASC, `b` DESC';
        $actual = $this->getSelect()->from('table')->orderBy(['a ASC', 'b DESC'])->toString();
        $this->assertEquals($expected, $actual);
    }

    public function testWhereExpr()
    {
        $expected = 'SELECT * FROM `table` WHERE (a > 5 AND b = 10)';
        $actual = $this->getSelect()->from('table')->where([
            'a > 5',
            'b = 10'
        ])->toString();

        $this->assertEquals($expected, $actual);
    }

    public function testWhereAuto()
    {
        $expected = 'SELECT * FROM `table` WHERE (`a` = 5 AND b = 10 AND `c` IN(1, 2, 3) AND `d` IS NULL)';
        $actual = $this->getSelect()->from('table')->where([
            'a'     => 5,
            'b = ?' => 10,
            'c'     => [1, 2, 3],
            'd'     => null
        ])->toString();

        $this->assertEquals($expected, $actual);

        $expected = 'SELECT * FROM `table` WHERE (`a` = 5 OR b = 10 OR `c` IN(1, 2, 3) OR `d` IS NULL)';
        $actual = $this->getSelect()->from('table')->where([
            'a'     => 5,
            'b = ?' => 10,
            'c'     => [1, 2, 3],
            'd'     => null
        ], Select::OP_OR)->toString();

        $this->assertEquals($expected, $actual);
    }

    public function testOrWhere()
    {
        $expected = 'SELECT * FROM `table` WHERE (`a` = 5 AND b = 10) OR (`a` = 15 AND b = 100)';
        $actual = $this->getSelect()
            ->from('table')
            ->where(['a' => 5, 'b = ?' => 10])
            ->orWhere(['a' => 15, 'b = ?' => 100])
            ->toString();

        $this->assertEquals($expected, $actual);
    }
}