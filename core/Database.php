<?php
namespace Forge\Core;

use PDO;
use PDOException;
use Exception;

/**
 * Database - ForgeORM Query Builder and Database wrapper.
 * Provides fluent interface for PDO prepared SQL queries to prevent SQL injections.
 */
class Database {
    private $pdo;
    private $table;
    private $select = '*';
    private $wheres = [];
    private $bindings = [];
    private $orderBy = '';
    private $limit = null;
    private $offset = null;

    public function __construct() {
        $app = App::getInstance();
        $config = $app->getConfig('db');
        
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
        
        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new Exception("Database Connection Failed: " . $e->getMessage());
        }
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function table($tableName) {
        // Reset query state for a new chain
        $this->table = $tableName;
        $this->select = '*';
        $this->wheres = [];
        $this->bindings = [];
        $this->orderBy = '';
        $this->limit = null;
        $this->offset = null;
        return $this;
    }

    public function select($columns) {
        $this->select = is_array($columns) ? implode(', ', $columns) : $columns;
        return $this;
    }

    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        // Generate unique parameter name to avoid conflict in bindings
        $paramName = "w_" . str_replace('.', '_', $column) . count($this->wheres);
        $this->wheres[] = "{$column} {$operator} :{$paramName}";
        $this->bindings[$paramName] = $value;
        
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy = "ORDER BY {$column} {$direction}";
        return $this;
    }

    public function limit($number) {
        $this->limit = (int) $number;
        return $this;
    }

    public function offset($number) {
        $this->offset = (int) $number;
        return $this;
    }

    private function buildSelectQuery() {
        $sql = "SELECT {$this->select} FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        
        if (!empty($this->orderBy)) {
            $sql .= " {$this->orderBy}";
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return $sql;
    }

    public function get() {
        $sql = $this->buildSelectQuery();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }

    public function first() {
        $this->limit(1);
        $sql = $this->buildSelectQuery();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        $result = $stmt->fetch();
        return $result ? $result : null;
    }

    public function insert(array $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(function($key) {
            return ":i_{$key}";
        }, array_keys($data)));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        
        $executeBindings = [];
        foreach ($data as $key => $val) {
            $executeBindings["i_{$key}"] = $val;
        }

        $stmt->execute($executeBindings);
        return $this->pdo->lastInsertId();
    }

    public function update(array $data) {
        $sets = [];
        $executeBindings = [];
        
        foreach ($data as $key => $val) {
            $paramName = "u_{$key}";
            $sets[] = "{$key} = :{$paramName}";
            $executeBindings[$paramName] = $val;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        
        // Merge updates bindings with where bindings
        $executeBindings = array_merge($executeBindings, $this->bindings);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($executeBindings);
        
        return $stmt->rowCount();
    }

    public function delete() {
        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        } else {
            throw new Exception("Deletes must have a where clause to avoid accidental truncation.");
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        
        return $stmt->rowCount();
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        if (stripos(trim($sql), 'select') === 0 || stripos(trim($sql), 'show') === 0 || stripos(trim($sql), 'describe') === 0) {
            return $stmt->fetchAll();
        }
        
        return $stmt->rowCount();
    }
}
