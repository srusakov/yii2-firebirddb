<?php

/**
 * CFirebirdCommandBuilder class file.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @modified by Ricardo Obregón <robregonm@gmail.com>
 * @updated by Sergey Rusakov <srusakov@gmail.com>
 */
namespace srusakov\firebirddb;

/**
 * CFirebirdCommandBuilder provides basic methods to create query commands for tables of Firebird Servers.
 *
 * @author idle sign <idlesign@yandex.ru>
 * @modified by Ricardo Obregón <robregonm@gmail.com>
 * @updated by Sergey Rusakov <srusakov@gmail.com>
 */
class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    public $typeMap = [
        Schema::TYPE_PK => 'integer NOT NULL PRIMARY KEY',
        Schema::TYPE_BIGPK => 'integer NOT NULL PRIMARY KEY',
        Schema::TYPE_STRING => 'varchar(255)',
        Schema::TYPE_TEXT => 'blob sub_type text',
        Schema::TYPE_SMALLINT => 'smallint',
        Schema::TYPE_INTEGER => 'integer',
        Schema::TYPE_BIGINT => 'integer',
        Schema::TYPE_FLOAT => 'float',
        Schema::TYPE_DECIMAL => 'decimal',
        Schema::TYPE_DATETIME => 'timestamp',
        Schema::TYPE_TIMESTAMP => 'timestamp',
        Schema::TYPE_TIME => 'time',
        Schema::TYPE_DATE => 'timestamp',
        Schema::TYPE_BINARY => 'blob',
        Schema::TYPE_BOOLEAN => 'smallint',
        Schema::TYPE_MONEY => 'decimal(19,4)',
    ];

    /**
     *
     * @var CDbCommand 
     */
    private $_command = null;

    /**
     * Returns the last insertion ID for the specified table.
     * @param mixed $table the table schema ({@link CDbTableSchema}) or the table name (string).
     * @return mixed last insertion id. Null is returned if no sequence name.
     */
    public function getLastInsertID($table)
    {
        if ($this->_command !== null) {
            $lastId = $this->_command->pdoStatement->fetchColumn();
            if ($lastId !== false) {
                return $lastId;
            }
        }
        return null;
    }


    /**
     * @inheritdoc
     * Firebird has its own SELECT syntax
     * SELECT [FIRST (<int-expr>)] [SKIP (<int-expr>)] <columns> FROM ...
     * @author srusakov@gmail.com
     */
    public function build($query, $params = [])
    {
      list($sql,$params) = parent::build($query, $params);
      if ($this->hasLimit($query->limit) and $this->hasOffset($query->offset) ) {
        $sql = preg_replace('/limit\s\d+/i', '', $sql, 1);
        $sql = preg_replace('/offset\s\d+/i', '', $sql, 1);
        $sql = preg_replace('/^SELECT /i', "SELECT FIRST {$query->limit} SKIP {$query->offset} ", $sql, 1);
      } elseif ($this->hasLimit($query->limit)) {
        $sql = preg_replace('/limit\s\d+/i', '', $sql, 1);
        $sql = preg_replace('/offset\s\d+/i', '', $sql, 1);
        $sql = preg_replace('/^SELECT /i', "SELECT FIRST {$query->limit} ", $sql, 1);
      } elseif ($this->hasOffset($query->offset) ) {
        $sql = preg_replace('/limit\s\d+/i', '', $sql, 1);
        $sql = preg_replace('/offset\s\d+/i', '', $sql, 1);
        $sql = preg_replace('/^SELECT /i', "SELECT SKIP {$query->offset} ", $sql, 1);
      }
      return [$sql,$params];
    }


    /**
     * Creates a SQL statement for resetting the sequence value of a table's primary key.
     * The sequence will be reset such that the primary key of the next new row inserted
     * will have the specified value or 1.
     * @param string $tableName the name of the table whose primary key sequence will be reset
     * @param mixed $value the value for the primary key of the next new row inserted. If this is not set,
     * the next new row's primary key will have a value 1.
     * @return string the SQL statement for resetting sequence
     * @throws InvalidParamException if the table does not exist or there is no sequence associated with the table.
     */
    public function resetSequence($tableName, $value = null)
    {
        $table = $this->db->getTableSchema($tableName);
        if ($table !== null && $table->sequenceName !== null) {
            $sequence = '"' . $table->sequenceName . '"';

            if (strpos($sequence, '.') !== false) {
                $sequence = str_replace('.', '"."', $sequence);
            }

            $tableName = $this->db->quoteTableName($tableName);
            if ($value === null) {
                $key = reset($table->primaryKey);
                $value = "(SELECT COALESCE(MAX(\"{$key}\"),0) FROM {$tableName})+1";
            } else {
                $value = (int) $value;
            }

            return "ALTER SEQUENCE {$sequence} RESTART WITH {$value}";
        } elseif ($table === null) {
            throw new InvalidParamException("Table not found: $tableName");
        } else {
            throw new InvalidParamException("There is not sequence associated with table '$tableName'.");
        }
    }
    

    /**
     * Creates an INSERT command.
     * @param mixed $table the table schema ({@link CDbTableSchema}) or the table name (string).
     * @param array $data data to be inserted (column name=>column value). If a key is not a valid column name, the corresponding value will be ignored.
     * @return CDbCommand insert command
     */
    public function createInsertCommand($table, $data)
    {
        $this->ensureTable($table);
        $fields = array();
        $values = array();
        $placeholders = array();
        $i = 0;
        foreach ($data as $name => $value) {
            if (($column = $table->getColumn($name)) !== null && ($value !== null || $column->allowNull)) {
                $fields[] = $column->rawName;
                if ($value instanceof CDbExpression) {
                    $placeholders[] = $value->expression;
                    foreach ($value->params as $n => $v)
                        $values[$n] = $v;
                } else {
                    $placeholders[] = self::PARAM_PREFIX . $i;
                    $values[self::PARAM_PREFIX . $i] = $column->typecast($value);
                    $i++;
                }
            }
        }
        if ($fields === array()) {
            $pks = is_array($table->primaryKey) ? $table->primaryKey : array($table->primaryKey);
            foreach ($pks as $pk) {
                $fields[] = $table->getColumn($pk)->rawName;
                $placeholders[] = 'NULL';
            }
        }

        $sql = "INSERT INTO {$table->rawName} (" . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';

        if (is_string($table->primaryKey) && ($column = $table->getColumn($table->primaryKey)) !== null && $column->type !== 'string') {
            $sql.=' RETURNING ' . $column->rawName;
            $command = $this->getDbConnection()->createCommand($sql);
            $table->sequenceName = $column->rawName;
        } else {
            $command = $this->getDbConnection()->createCommand($sql);
        }

        foreach ($values as $name => $value) {
            $command->bindValue($name, $value);
        }

        $this->_command = $command;

        return $command;
    }

}
