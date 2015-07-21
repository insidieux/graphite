<?php
namespace Graphite\Db\ActiveRecord;

use Graphite\Db\Exception;
use Graphite\Db\Query\Select;

class Finder extends Select
{
    /**
     * @var string
     */
    protected $modelClass;

    /**
     * @var bool
     */
    protected $returnRaw = false;


    /**
     * @param string $modelClass
     */
    public function __construct($modelClass = '')
    {
        if (!empty($modelClass)) {
            $this->setModelClass($modelClass);
        }
    }

    /**
     * @param string $className
     *
     * @return static
     */
    public function setModelClass($className)
    {
        $this->modelClass = $className;
        return $this;
    }

    /**
     * @return string
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }

    /**
     * @param bool $flag
     *
     * @return static
     */
    public function asRawResult($flag = true)
    {
        $this->returnRaw = (bool) $flag;
        return $this;
    }

    /**
     * @return Model[]|\Graphite\Db\ResultSet
     *
     * @throws Exception
     */
    public function run()
    {
        if (empty($this->modelClass) || !is_subclass_of($this->modelClass, Model::class)) {
            $format = 'Bad Model class name "%s". Must be subclass of "%s"';
            throw new Exception(sprintf($format, $this->modelClass, Model::class));
        }

        if ($this->getConnection()->isProfilerEnabled()) {
            $this->getConnection()->getProfiler()->setStaticCaller($this->modelClass);
        }

        $result = parent::run();

        if ($this->returnRaw) {
            return $result;
        }

        // create models
        $models = [];
        $modelClass = $this->modelClass;
        foreach ($result->fetchAll() as $row) {
            /** @var Model $model */
            $model = new $modelClass($row, true);
            $model->afterFind();
            $models[] = $model;
        }

        return $models;
    }
}
