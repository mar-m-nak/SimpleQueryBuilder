<?php

namespace YourProperNameSpace;

use mysqli;

/**
 * 簡易クエリビルダ―
 */
class SimpleQueryBuilder
{

    private $tableName = '';
    private $selectColumns = [];
    private $insertColumns = [];
    private $updateColumns = [];
    private $deleteTable = '';
    private $join = [];
    private $wheres = [];
    private $orders = [];
    private $limit = '';
    private $offset = '';

    /**
     * 簡易クエリビルダ―
     * - $column, $operator, $value の指定で Where 条件文を呼び出し時に登録することも可
     *
     * @param string $column 省略可
     * @param string $operator 省略可
     * @param string|integer|array|null|boolean $value 省略可
     * @return SimpleQueryBuilder
     */
    public function __construct(string $column = null, string $operator = null, $value = 0)
    {
        if (!is_null($column) && !is_null($operator) && $value !== 0) {
            // 条件を登録
            $this->where($column, $operator, $value);
        }
    }

    /**
     * テーブル名を登録
     *
     * @param string $sql
     * @return SimpleQueryBuilder
     */
    public function from(string $tableName): SimpleQueryBuilder
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Join 文を登録する
     *
     * @param string $type
     * @param string $cond
     * @return SimpleQueryBuilder
     */
    public function join(string $type, string $cond): SimpleQueryBuilder
    {
        $this->join[] = ['type' => $type, 'cond' => $cond];
        return $this;
    }

    /**
     * Where 条件を登録
     * - $value に配列を指定した場合、その配列の値群は OR で連結される（$arraysOperator で変更可）
     *
     * @param string $column
     * @param string $operator
     * @param string|integer|array|null|boolean $value
     * @param boolean $refuseEmpty 省略可 $value が empty の時（true:登録しない / false:条件として登録する）
     * @param string $arraysOperator 省略可 $value に配列を指定した場合の連結演算子
     * @return SimpleQueryBuilder
     */
    public function where(
        string $column,
        string $operator,
        $value,
        bool $refuseEmpty = true,
        string $arraysOperator = 'OR'

    ): SimpleQueryBuilder {
        if ($refuseEmpty && empty($value)) {
            return $this;
        }
        $this->wheres = [];
        $this->andWhere($column, $operator, $value, $refuseEmpty, $arraysOperator);
        return $this;
    }

    /**
     * Where 条件を登録
     * - ビルド時には各条件は AND で連結される
     * - $value に配列を指定した場合、その配列の値群は OR で連結される（$arraysOperator で変更可）
     *
     * @param string $column
     * @param string $operator
     * @param string|integer|array|null|boolean $value
     * @param boolean $refuseEmpty 省略可 $value が empty の時（true:登録しない / false:条件として登録する）
     * @param string $arraysOperator 省略可 $value に配列を指定した場合の連結演算子
     * @return SimpleQueryBuilder
     */
    public function andWhere(
        string $column,
        string $operator,
        $value,
        bool $refuseEmpty = true,
        string $arraysOperator = 'OR'
    ): SimpleQueryBuilder {
        if ($refuseEmpty && empty($value)) {
            return $this;
        }
        if (is_array($value)) {
            // 配列内の値を展開して登録
            $conditions = [];
            foreach ($value as $value) {
                $conditions[] = $this->makeCond($column, $operator, $value);
            }
            $cond = $this->buildWheres($conditions, $arraysOperator);
            $this->wheres[] = "(\n{$cond})";
        } else {
            // 登録
            $this->wheres[] = $this->makeCond($column, $operator, $value);
        }
        return $this;
    }

    /**
     * カラムと値を演算子で繋いだ文字列を返す
     *
     * @param string $column
     * @param string $operator
     * @param string|integer|null|boolean $value
     * @return string
     */
    private function makeCond(string $column, string $operator, $value): string
    {
        if (is_string($value) || is_int($value)) {
            return "{$column} {$operator} " . self::castToStrOrInt($value);
        } elseif (is_null($value)) {
            $operator = $operator === '=' ? 'IS' : $operator;
            $operator = in_array($operator, ['!=', '<>'], true) ? 'IS NOT' : $operator;
            return "{$column} {$operator} NULL";
        } elseif (is_bool($value)) {
            $value = $value ? 'TRUE' : 'FALSE';
            return "{$column} {$operator} '{$value}'";
        }
        return '';
    }

    /**
     * 特殊文字をエスケープして返す
     *
     * @param string $str
     * @return string
     */
    private static function escapeString(string $str): string
    {
        // TODO: ここで適当なエスケープを行う
        // $str = mysqli_real_escape_string($str);
        // とか
        // $str = pg_escape_string($str);
        // など
        return $str;
    }

    /**
     * Where 条件文配列を AND / OR で連結して返す
     *
     * @param array $wheres
     * @param string $operator 省略時 AND
     *
     * @return string
     */
    private function buildWheres(array $wheres, string $operator = 'AND'): string
    {
        if (empty($wheres)) {
            return '';
        }
        return implode(" {$operator}\n", $wheres) . "\n";
    }

    /**
     * Order 条件を登録
     *
     * @param string $column
     * @param string $order
     * @return SimpleQueryBuilder
     */
    public function order(string $column, string $order = ''): SimpleQueryBuilder
    {
        $this->orders[] = "{$column} {$order}";
        return $this;
    }

    /**
     * Order 条件文配列を , で連結して返す
     *
     * @return string
     */
    private function buildOrders(): string
    {
        if (empty($this->orders)) {
            return '';
        }
        return implode(', ', $this->orders) . "\n";
    }

    /**
     * Limit を登録
     *
     * @param integer $limit
     * @return SimpleQueryBuilder
     */
    public function limit(int $limit): SimpleQueryBuilder
    {
        $this->limit = "LIMIT {$limit}\n";
        return $this;
    }

    /**
     * Offset を登録
     *
     * @param integer $offset
     * @return SimpleQueryBuilder
     */
    public function offset(int $offset): SimpleQueryBuilder
    {
        $this->offset = "OFFSET {$offset}\n";
        return $this;
    }

    /**
     * 登録カラムをクリア
     *
     * @return void
     */
    private function clearColumns()
    {
        $this->selectColumns = [];
        $this->insertColumns = [];
        $this->updateColumns = [];
        $this->deleteTable = '';
    }

    /**
     * Select するカラムを登録
     *
     * @param string|array $columns
     * @return SimpleQueryBuilder
     */
    public function select($columns): SimpleQueryBuilder
    {
        $this->clearColumns();
        if (is_string($columns)) {
            $this->selectColumns[] = $columns;
        }
        if (is_array($columns)) {
            $this->selectColumns = $columns;
        }
        return $this;
    }

    /**
     * Update カラムと値を登録
     * - 値に計算を含む場合は手動でエスケープしたうえで文字列で登録し、第3引数にfalseを指定。 e.g. "'data' + 1"
     *
     * @param string $tableName
     * @param array $columns ['clm1', 'clm2', ...]
     * @param array $values ['val1', 'val2', ... ]
     * @param boolean $doCastAndEscape 値を文字列/数値へキャスト＆エスケープする (省略=true)
     * @return SimpleQueryBuilder
     */
    public function update(
        string $tableName,
        array $columns,
        array $values,
        bool $doCastAndEscape = true
    ): SimpleQueryBuilder {
        $this->clearColumns();
        $this->tableName = $tableName;
        if (count($columns) != count($values)) {
            return $this;
        }
        foreach ($columns as $key => $column) {
            $value = $doCastAndEscape ? self::castToStrOrInt($values[$key]) : $values[$key];
            if (!$doCastAndEscape && !is_string($value)) {
                trigger_error(
                    '簡易クエリビルダ―エラー: update()の値に文字列型以外を指定する場合は第3引数にtrueを指定してください',
                    E_USER_ERROR
                );
                exit(1);
            }
            $this->updateColumns[strval($column)] = $value;
        }
        return $this;
    }

    /**
     * Insert カラムと値を登録
     *
     * @param string $tableName
     * @param array $columns ['clm1', 'clm2', ...]
     * @param array ...$values ['val1a', 'val2a', ... ], ['val1b', 'val2b', ... ], ...
     * @return SimpleQueryBuilder
     */
    public function insert(string $tableName, array $columns, array ...$values): SimpleQueryBuilder
    {
        $this->clearColumns();
        $this->tableName = $tableName;
        // 要素数チェック
        $countColumns = count($columns);
        foreach ($values as $oneValues) {
            if ($countColumns != count($oneValues)) {
                trigger_error('簡易クエリビルダ―エラー: insert()のカラムと値の要素数を揃えてください', E_USER_ERROR);
                exit(1);
            }
        }
        // 登録
        $this->insertColumns['columns'] = $columns;
        foreach ($values as $key1 => $oneValues) {
            foreach ($oneValues as $key2 => $value) {
                $values[$key1][$key2] = self::castToStrOrInt($value);
            }
        }
        $this->insertColumns['values'] = $values;
        return $this;
    }

    /**
     * Delete テーブル名を登録
     *
     * @param string $tableName
     * @return SimpleQueryBuilder
     */
    public function delete(string $tableName): SimpleQueryBuilder
    {
        $this->clearColumns();
        $this->deleteTable = $tableName;
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * 引数を文字列化して返す
     * - string: そのまま
     * - int: 文字列化
     * - bool: 'TRUE' / 'FALSE'
     * - null: 'NULL'
     *
     * @param string|integer|boolean|null $value
     * @return string|integer
     */
    private static function castToStrOrInt($value)
    {
        if (is_string($value)) {
            return "'" . self::escapeString($value) . "'";
        } elseif (is_int($value)) { // TODO: x86系では 2147483648 以上NG
            return strval($value);
        } elseif (is_null($value)) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        return '';
    }


    /**
     * SQLを組み立てて返す
     *
     * @return string
     */
    public function build(): string
    {
        if (
            $this->tableName === '' ||
            (
                empty($this->selectColumns) &&
                empty($this->insertColumns) &&
                empty($this->updateColumns) &&
                empty($this->deleteTable)
            )
        ) {
            trigger_error('簡易クエリビルダ―エラー: テーブル名またはカラムが指定されていません', E_USER_ERROR);
            exit(1);
        }

        $concatenationJoin = function () {
            if (empty($this->join)) {
                return '';
            }
            $joins = [];
            foreach ($this->join as $join) {
                $joins[] = "JOIN {$join['type']} {$join['cond']}";
            }
            return implode(",\n", $joins) . "\n";
        };

        // SQL組み立て
        $sql = '';
        if ($this->selectColumns) {
            $sql = "SELECT\n" . implode(",\n", $this->selectColumns) . "\nFROM `{$this->tableName}`\n";
            $sql .= $concatenationJoin();
            if (!empty($this->wheres)) {
                $sql .= "WHERE\n" . $this->buildWheres($this->wheres);
            }
            if (!empty($this->orders)) {
                $sql .= 'ORDER BY ' . $this->buildOrders();
            }
            $sql .= $this->limit;
            $sql .= $this->offset;
            $sql .= ';';
        }

        if ($this->updateColumns) {
            $sql = "UPDATE `{$this->tableName}`\n";
            $sql .= $concatenationJoin();
            $sql .= "SET\n";
            $columnAndValues = [];
            foreach ($this->updateColumns as $column => $value) {
                $columnAndValues[] = "{$column} = {$value}";
            }
            $sql .= implode(",\n", $columnAndValues) . "\n";
            if (!empty($this->wheres)) {
                $sql .= "WHERE\n" . $this->buildWheres($this->wheres);
            }
            $sql .= ';';
        }

        if ($this->insertColumns) {
            $sql = "INSERT INTO `{$this->tableName}`\n";
            $sql .= '(' . implode(", ", $this->insertColumns['columns']) . ")\n";
            $sql .= "VALUES\n";
            $oneValues = [];
            foreach ($this->insertColumns['values'] as $oneValue) {
                $oneValues[] = '(' . implode(", ", $oneValue) . ")";
            }
            $sql .= implode(",\n", $oneValues) . "\n";
            $sql .= ';';
        }

        if ($this->deleteTable) {
            $sql = "DELETE FROM `{$this->tableName}`\n";
            $sql .= $concatenationJoin();
            if (!empty($this->wheres)) {
                $sql .= "WHERE\n" . $this->buildWheres($this->wheres);
            }
            $sql .= ';';
        }

        return $sql;
    }

    /**
     * クエリを実行して結果を返す
     *
     * @return array
     */
    public function exec(): array
    {
        $sql = $this->build();
        // TODO: ここでDB問い合わせ、適当に返す

        return [];
    }
}
