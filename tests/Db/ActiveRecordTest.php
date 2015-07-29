<?php
namespace tests\Db;

class TestModel extends \Graphite\Db\ActiveRecord\Model
{}

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
        $this->assertEquals(\Graphite\Db\ActiveRecord\Finder::class, TestModel::getFinderClass());
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
        $this->assertInstanceOf(\Graphite\Db\ActiveRecord\Finder::class, $query);
        $this->assertEquals("SELECT * FROM `testmodel`", $query->toString());

        $query = TestModel::insertGlobal();
        $this->assertInstanceOf(\Graphite\Db\Query\Insert::class, $query);
        $this->assertEquals("INSERT INTO `testmodel`", $query->toString());

        $query = TestModel::updateGlobal();
        $this->assertInstanceOf(\Graphite\Db\Query\Update::class, $query);
        $this->assertEquals("UPDATE `testmodel`", $query->toString());

        $query = TestModel::deleteGlobal();
        $this->assertInstanceOf(\Graphite\Db\Query\Delete::class, $query);
        $this->assertEquals("DELETE FROM `testmodel`", $query->toString());
    }
}
