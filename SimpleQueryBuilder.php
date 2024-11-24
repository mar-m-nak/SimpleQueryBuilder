<?php

namespace YourProperNameSpace;

/**
 * 簡易クエリビルダ―
 */
class SimpleQueryBuilder
{

    private $tableName = '';
    private $selectColumns = [];
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
    public function __construct(string $column = null, string $operator = null, $value = 0): SimpleQueryBuilder
    {
        if (!is_null($column) && !is_null($operator) && $value !== 0) {
            // 条件を登録
            $this->where($column, $operator, $value);
        }
        return $this;
    }

    /**
     * テーブル名を登録
     *
     * @param string $sql
     * @return SimpleQueryBuilder
     */
    protected function from(string $tableName): SimpleQueryBuilder
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
     * カラム・演算子・値 を Where 条件文として整形して返す
     *
     * @param string $column
     * @param string $operator
     * @param string|integer|null|boolean $value
     * @return string
     */
    private function makeCond(string $column, string $operator, $value): string
    {
        if (is_string($value)) {
            return "{$column} {$operator} '" . self::escapeString($value) . "'";
        } elseif (is_int($value)) { // TODO: x86系では 2147483648 以上NG
            return "{$column} {$operator} {$value}";
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
     * Select するカラムを登録
     *
     * @param string|array $columns
     * @return SimpleQueryBuilder
     */
    public function select($columns): SimpleQueryBuilder
    {
        $this->selectColumns = [];
        if (is_string($columns)) {
            $this->selectColumns[] = $columns;
        }
        if (is_array($columns)) {
            $this->selectColumns = $columns;
        }
        return $this;
    }

    /**
     * SQLを組み立てて返す
     *
     * @return string
     */
    public function build(): string
    {
        if ($this->tableName === '' || empty($this->selectColumns)) {
            trigger_error('簡易クエリビルダ―エラー：テーブル名またはカラムが指定されていません', E_USER_ERROR);
            exit(1);
        }
        // SQL組み立て
        $sql = "SELECT\n" . implode(",\n", $this->selectColumns) . "\nFROM {$this->tableName}\n";
        if (!empty($this->join)) {
            foreach ($this->join as $join) {
                $sql .= "JOIN {$join['type']} {$join['cond']}\n";
            }
        }
        if (!empty($this->wheres)) {
            $sql .= "WHERE\n" . $this->buildWheres($this->wheres);
        }
        if (!empty($this->orders)) {
            $sql .= 'ORDER BY ' . $this->buildOrders();
        }
        $sql .= $this->limit;
        $sql .= $this->offset;

        return $sql;
    }

    /**
     * クエリを実行して結果を返す
     *
     * @return array
     */
    public function get(): array
    {
        $sql = $this->build();
        // TODO: ここでDB問い合わせ、適当に返す

        return [];
    }
}
