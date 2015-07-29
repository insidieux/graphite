<?php
namespace Graphite\Db\ActiveRecord;

use Graphite\Db\Connection;
use Graphite\Db\Query\Insert;
use Graphite\Db\Query\Update;
use Graphite\Db\Query\Delete;
use Graphite\Db\Exception;

/**
 * @todo поведения (Timestamp, Serialized, Diff, etc...)
 * @todo relations (hasOne, hasMany)
 * @todo возможность хуками отменять сохранение/изменение/удаление моделей
 * @todo подумать над инкрементами
 */
class Model implements \JsonSerializable
{
    /**
     * @var Connection
     */
    protected static $conn;

    /**
     * @var array
     */
    private $attrs = [];

    /**
     * @var
     */
    private $oldAttrs = [];

    /**
     * @var bool
     */
    private $fetched = false;

    /**
     * @var
     */
    private $deleted = false;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * Returns ActiveRecord default connection
     *
     * @return Connection
     */
    public static function getConnection()
    {
        return static::$conn;
    }

    /**
     * Set ActiveRecord default connection
     *
     * @param Connection $conn
     */
    public static function setConnection(Connection $conn)
    {
        static::$conn = $conn;
    }

    /**
     * Returns db table name associated with model
     *
     * @return string
     */
    public static function getTable()
    {
        return strtolower(ltrim(strrchr(static::class, '\\'), '\\'));
    }

    /**
     * Returns primary key column name. By default "id"
     *
     * @return string
     */
    public static function getPK()
    {
        return 'id';
    }

    /**
     * Returns ActiveRecordFinder class name to be used to find models.
     * By default ActiveRecordFinder
     *
     * @return string
     */
    public static function getFinderClass()
    {
        return Finder::class;
    }

    /**
     * Returns array of model attributes labels
     *
     * @return array
     */
    public static function getLabels()
    {
        return [];
    }


    /* --- Model ---------------------------------------------------------------------------------------------------- */

    /**
     * @param array $attrs   initial model attrs values
     * @param bool  $fetched true if model was fetched from db, false otherwise
     */
    public function __construct($attrs = [], $fetched = false)
    {
        $this->init();

        if (!empty($attrs)) {
            // attrs setting in first time, so we can directly set it into internal attrs
            $this->attrs = $attrs + $this->attrs;
        }

        $this->setFetched($fetched);
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        return isset($this->attrs[$name]) ? $this->attrs[$name] : null;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        // copy current to old values
        if (!array_key_exists($name, $this->oldAttrs)) {
            $this->oldAttrs[$name] = $this->$name;
        }

        $this->attrs[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->attrs[$name]);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->attrs)) {
            unset($this->attrs[$name]);
        }
    }

    /**
     * @param bool $flag
     */
    public function setFetched($flag = true)
    {
        $this->fetched = (bool) $flag;
    }

    /**
     * @return bool
     */
    public function isFetched()
    {
        return $this->fetched;
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        return !$this->fetched;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param string $attr
     *
     * @return bool
     */
    public function isDirty($attr = null)
    {
        if ($attr === null) {
            return count($this->oldAttrs) > 0;
        } else {
            return array_key_exists($attr, $this->oldAttrs);
        }
    }

    /**
     * @return bool
     */
    public function isClean()
    {
        return !$this->isDirty();
    }

    /**
     * Вернет список измененыых аттрибутов модели.
     *
     * @param bool $withOldValues
     *
     * @return array
     */
    public function getDirty($withOldValues = false)
    {
        return $withOldValues ? $this->oldAttrs : array_keys($this->oldAttrs);
    }

    /**
     * Make model state clean (unchanged)
     */
    public function makeClean()
    {
        $this->oldAttrs = [];
    }

    /**
     * Set model attributes values from array.
     *
     * @param array    $attrs key - attr name, value - attr value
     * @param string[] $mask attrs names from $attrs to be only set
     *
     * @return self
     */
    public function assign($attrs, array $mask = null)
    {
        if (!empty($mask)) {
            foreach ($mask as $name) {
                if (isset($attrs[$name])) {
                    $this->$name = $attrs[$name];
                }
            }
        } else {
            foreach ($attrs as $name => $value) {
                $this->$name = $value;
            }
        }
        return $this;
    }

    /**
     * @param string[] $mask
     *
     * @return array
     */
    public function toArray(array $mask = null)
    {
        if (!empty($mask)) {
            $attrs = [];
            foreach ($mask as $attr) {
                $attrs[$attr] = $this->$attr;
            }

            return $attrs;
        } else {
            return $this->attrs;
        }
    }

    /**
     * @param string|string[] $mask
     *
     * @return string
     */
    public function toJson($mask = null)
    {
        return json_encode($this->toArray($mask));
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }


    /* --- Model Validation ----------------------------------------------------------------------------------------- */

    /**
     * Validate model attrs values.
     *
     * @return bool true if model attrs values are valid. false otherwise
     */
    public function validate()
    {
        $this->clearErrors();
        return true;
    }

    /**
     * Returns true if model attrs values are valid. false otherwise
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->validate();
    }

    /**
     * Returns validation errors array. Key - attr name, value - array of errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Reset validation errors
     */
    public function clearErrors()
    {
        $this->errors = [];
    }


    /* --- Model CRUD ----------------------------------------------------------------------------------------------- */

    /**
     * Returns origin (unchanged) PK model value
     *
     * @return mixed|null
     */
    protected function getRealPK()
    {
        $pkCol = static::getPK();
        $pkVal = $this->isDirty($pkCol) ? $this->oldAttrs[$pkCol] : $this->$pkCol;

        return $pkVal;
    }

    /**
     * Insert model into db
     *
     * @return bool
     */
    public function insert()
    {
        if ($this->isDeleted()) {
            return false;
        }

        $this->beforeInsert();

        $res = static::insertGlobal()
            ->values($this->attrs)
            ->run();

        if ($res) {
            // set new id
            $this->attrs[static::getPK()] = $this->getConnection()->getLastInsertId();

            $this->afterInsert();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Updates model in db
     *
     * @return bool
     */
    public function update()
    {
        if ($this->isDeleted() || !$this->isDirty()) {
            return false;
        }

        if (!($pkVal = $this->getRealPK())) {
            return false;
        }

        $this->beforeUpdate();

        $res = static::updateGlobal()
            ->set($this->toArray($this->getDirty()))
            ->where([static::getPK() => $pkVal])
            ->run();

        if ($res) {
            $this->afterUpdate();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete model from db and mark model as "deleted"
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->isDeleted()) {
            return false;
        }

        if (!($pkVal = $this->getRealPK())) {
            return false;
        }

        $this->beforeDelete();

        $res = static::deleteGlobal()
            ->where([static::getPK() => $pkVal])
            ->run();

        $this->afterDelete();

        if ($res) {
            $this->deleted = true;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Insert record to DB if model is new, or update it
     *
     * @return bool
     */
    public function save()
    {
        $this->beforeSave();
        $result = $this->isNew() ? $this->insert() : $this->update();
        $this->afterSave();

        return $result;
    }


    /* --- Model Hooks ---------------------------------------------------------------------------------------------- */

    public function init()
    {}

    public function afterFind()
    {}

    public function beforeSave()
    {}

    public function afterSave()
    {}

    public function beforeInsert()
    {}

    public function afterInsert()
    {}

    public function beforeUpdate()
    {}

    public function afterUpdate()
    {}

    public function beforeDelete()
    {}

    public function afterDelete()
    {}


    /* --- Finders -------------------------------------------------------------------------------------------------- */

    /**
     * General records search method. Returns ActiveRecordFinder
     *
     * @return Finder
     *
     * @throws Exception
     */
    public static function find()
    {
        $finderClass = static::getFinderClass();
        if (!class_exists($finderClass)) {
            throw new Exception('Bad finder class name');
        }

        /** @var Finder $finder */
        $finder = new $finderClass();

        if (!($finder instanceof Finder)) {
            throw new Exception('Bad finder class');
        }

        return $finder
            ->setModelClass(static::class)
            ->setConnection(static::getConnection())
            ->from(static::getTable());
    }

    /**
     * Returns all table rows as models
     *
     * @return static[]
     */
    public static function findAll()
    {
        return static::find()->run();
    }

    /**
     * Search models by its PK values
     *
     * @param int|int[] $id
     *
     * @return static|static[]
     */
    public static function findPK($id)
    {
        $models = self::find()
            ->where([static::getPK() => $id])
            ->run();

        if (is_array($id)) {
            return $models;
        } else {
            return empty($models) ? null : reset($models);
        }
    }

    /**
     * Search models by array of criteria
     *
     * @param array $criteria same as \Graphite\Db\Query\AbstractQuery::where
     *
     * @return static[]
     */
    public static function findBy(array $criteria)
    {
        $models = self::find()
            ->where($criteria)
            ->run();

        return $models;
    }

    /**
     * Return Graphite\Db\Query\Insert object for ActiveRecord table
     *
     * @return Insert
     */
    public static function insertGlobal()
    {
        return (new Insert(static::getConnection()))
            ->into(static::getTable());
    }

    /**
     * Return Graphite\Db\Query\Update object for ActiveRecord table
     *
     * @return Update
     */
    public static function updateGlobal()
    {
        return (new Update(static::getConnection()))
            ->table(static::getTable());
    }

    /**
     * Return Graphite\Db\Query\Delete query object for ActiveRecord table
     *
     * @return Delete
     */
    public static function deleteGlobal()
    {
        return (new Delete(static::getConnection()))
            ->from(static::getTable());
    }
}
