<?php
namespace Forge\Core;

/**
 * Model - Base ActiveRecord Model for database object mapping.
 * Subclasses represent database tables, mapping records to PHP objects.
 */
abstract class Model {
    protected static $table = null;
    protected static $primaryKey = 'id';
    protected $attributes = [];
    protected $original = [];

    public function __construct(array $attributes = []) {
        $this->fill($attributes);
    }

    /**
     * Get the table name dynamically if not explicitly defined
     */
    public static function getTableName() {
        if (static::$table !== null) {
            return static::$table;
        }
        
        // Simple pluralization: ClassName -> classnames -> classnames + 's'
        $classParts = explode('\\', get_called_class());
        $className = end($classParts);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
    }

    /**
     * Get the primary key name
     */
    public static function getPrimaryKey() {
        return static::$primaryKey;
    }

    /**
     * Get database service instance
     */
    protected static function getDb() {
        return App::getInstance()->get('database');
    }

    /**
     * Fill the model with an array of attributes
     */
    public function fill(array $attributes) {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Magic getter for attributes
     */
    public function __get($key) {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Magic setter for attributes
     */
    public function __set($key, $value) {
        $this->attributes[$key] = $value;
    }

    /**
     * Find record by primary key ID
     */
    public static function find($id) {
        $db = self::getDb();
        $tableName = static::getTableName();
        $pk = static::getPrimaryKey();

        $data = $db->table($tableName)->where($pk, '=', $id)->first();
        if ($data) {
            $instance = new static($data);
            $instance->original = $data;
            return $instance;
        }
        return null;
    }

    /**
     * Query wrapper: starts a Database chain on the model's table
     */
    public static function query() {
        $db = self::getDb();
        return $db->table(static::getTableName());
    }

    /**
     * Get all records for this model
     */
    public static function all() {
        $db = self::getDb();
        $records = $db->table(static::getTableName())->get();
        
        $instances = [];
        foreach ($records as $record) {
            $instance = new static($record);
            $instance->original = $record;
            $instances[] = $instance;
        }
        return $instances;
    }

    /**
     * Save the model instance to database (Insert or Update)
     */
    public function save() {
        $db = self::getDb();
        $tableName = static::getTableName();
        $pk = static::getPrimaryKey();
        
        if (isset($this->attributes[$pk])) {
            // Update
            $id = $this->attributes[$pk];
            $dataToUpdate = array_diff_assoc($this->attributes, $this->original);
            
            // Only update if there are changes
            if (!empty($dataToUpdate)) {
                $db->table($tableName)->where($pk, '=', $id)->update($dataToUpdate);
                $this->original = $this->attributes;
            }
            return true;
        } else {
            // Insert
            $id = $db->table($tableName)->insert($this->attributes);
            if ($id) {
                $this->attributes[$pk] = $id;
                $this->original = $this->attributes;
                return $id;
            }
            return false;
        }
    }

    /**
     * Delete this model instance from database
     */
    public function delete() {
        $pk = static::getPrimaryKey();
        if (!isset($this->attributes[$pk])) {
            return false;
        }

        $db = self::getDb();
        $tableName = static::getTableName();
        $id = $this->attributes[$pk];

        $deleted = $db->table($tableName)->where($pk, '=', $id)->delete();
        if ($deleted) {
            $this->attributes = [];
            $this->original = [];
            return true;
        }
        return false;
    }
}
