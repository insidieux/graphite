<?php
namespace tests\Db;

use Graphite\Db\ActiveRecord\Finder;
use Graphite\Db\Query\Delete;
use Graphite\Db\Query\Insert;
use Graphite\Db\Query\Update;
use tests\Fixtures\TestModel;

class ActiveRecordTest extends DatabaseTestCase
{
    public function setUp()
    {
        \Graphite\Db\ActiveRecord\Model::setConnection(self::$conn);
    }

    public function testModelDefaults()
    {
        $this->assertEquals('testmodel', TestModel::getTable());
        $this->assertEquals('id', TestModel::getPK());
        $this->assertEquals(Finder::class, TestModel::getFinderClass());
    }

    public function testAttributes()
    {
        $attributes = ['age' => 10, 'sex' => 1];

        $model = new TestModel();

        $model->name = 'alex';
        $this->assertEquals('alex', $model->name);
        $this->assertTrue(isset($model->name));

        $model->assign($attributes);
        $this->assertEquals(10, $model->age);
        $this->assertEquals(1, $model->sex);

        unset($model->name);
        $this->assertNull($model->name);

        // construct assignments
        $model = new TestModel($attributes);
        $this->assertEquals(10, $model->age);
        $this->assertEquals(1,  $model->sex);

        // assignment mask
        $model = new TestModel();
        $model->assign($attributes, ['age']);
        $this->assertEquals(10, $model->age);
        $this->assertNull($model->sex);
    }

    public function testStates()
    {
        $model = new TestModel();

        $this->assertTrue($model->isClean());
        $this->assertFalse($model->isDirty());

        $model->name = 'name';
        $this->assertTrue($model->isDirty());
        $this->assertTrue($model->isDirty('name'));
        $this->assertFalse($model->isClean());

        $model->makeClean();
        $this->assertTrue($model->isClean());
        $this->assertFalse($model->isDirty());
    }

    public function testNonDirtyOnEqualsSet()
    {
        $model = new TestModel([
            'a' => 1,
            'b' => 'foo',
        ]);

        $model->makeClean();

        $model->a = 1;
        $model->b = 'foo';
        $model->c = null;

        $this->assertFalse($model->isDirty('a'));
        $this->assertFalse($model->isDirty('b'));
        $this->assertFalse($model->isDirty('c'));
    }

    public function testExports()
    {
        $attributes = ['name' => 'name', 'age' => 10];

        $model = new TestModel($attributes);
        $this->assertEquals($attributes, $model->toArray());
        $this->assertEquals(['age' => 10], $model->toArray(['age']));
        $this->assertEquals(json_encode($attributes), $model->toJson());
        $this->assertEquals(json_encode($attributes), json_encode($model));
    }

    public function testGlobalQueries()
    {
        $query = TestModel::find();
        $this->assertInstanceOf(Finder::class, $query);
        $this->assertEquals("SELECT * FROM `testmodel`", $query->toString());

        $query = TestModel::insertGlobal();
        $this->assertInstanceOf(Insert::class, $query);
        $this->assertEquals("INSERT INTO `testmodel`", $query->toString());

        $query = TestModel::updateGlobal();
        $this->assertInstanceOf(Update::class, $query);
        $this->assertEquals("UPDATE `testmodel`", $query->toString());

        $query = TestModel::deleteGlobal();
        $this->assertInstanceOf(Delete::class, $query);
        $this->assertEquals("DELETE FROM `testmodel`", $query->toString());
    }
}
