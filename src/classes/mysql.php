<?php

class mysql
{

    /** @var mysqli $link */
    private $link = null;

    public function __construct(string $host, string $user, string $password, string $name)
    {
        $this->link = new mysqli();
        if (!$this->link->real_connect($host, $user, $password, $name)) {
            $this->link = null;
            throw new Exception('Could not connect to MySQL');
        }
    }

    public function __destruct()
    {
        if ($this->link)
            $this->link->close();
    }

    public function __get($k)
    {
        if ($k == 'error') {
            return $this->link->error;
        }
        return $this->$k;
    }

    public function query(string $sql)
    {
        if (func_num_args() > 1) {
            $mark_count = substr_count($sql, '?');
            $positions = array();
            $last_pos = -1;
            for ($i = 0; $i < $mark_count; $i++) {
                $last_pos = strpos($sql, '?', $last_pos + 1);
                $positions[] = $last_pos;
            }
            for ($i = $mark_count - 1; $i >= 0; $i--) {
                $arg_val = func_get_arg($i + 1);
                if (is_null($arg_val)) {
                    $v = 'NULL';
                } else {
                    $v = '\'' . $this->escape($arg_val) . '\'';
                }
                $sql = substr_replace($sql, $v, $positions[$i], 1);
            }
        }

        $q = $this->link->query($sql);
        if (!$q) {
            $error = $this->link->error;
            trigger_error($error, E_USER_WARNING);
            return false;
        }

        return $q;
    }

    public function insert(string $table, array $fields)
    {
        return $this->performInsert('INSERT', $table, $fields);
    }

    public function replace(string $table, array $fields)
    {
        return $this->performInsert('REPLACE', $table, $fields);
    }

    protected function performInsert(string $command, string $table, array $fields)
    {
        $names = [];
        $values = [];
        $count = 0;
        foreach ($fields as $k => $v) {
            $names[] = $k;
            $values[] = $v;
            $count++;
        }

        $sql = "{$command} INTO `{$table}` (`" . implode('`, `', $names) . "`) VALUES (" . implode(', ', array_fill(0, $count, '?')) . ")";
        array_unshift($values, $sql);

        return call_user_func_array([$this, 'query'], $values);
    }

    public function multipleInsert(string $table, array $rows)
    {
        return $this->performMultipleInsert('INSERT', $table, $rows);
    }

    public function multipleReplace(string $table, array $rows)
    {
        return $this->performMultipleInsert('REPLACE', $table, $rows);
    }

    protected function performMultipleInsert(string $command, string $table, array $rows)
    {
        $names = [];
        $sql_rows = [];
        foreach ($rows as $i => $fields) {
            $row_values = [];
            foreach ($fields as $field_name => $field_val) {
                if ($i == 0) {
                    $names[] = $field_name;
                }
                $row_values[] = $this->escape($field_val);
            }
            $sql_rows[] = "('" . implode("', '", $row_values) . "')";
        }

        $sql = "{$command} INTO `{$table}` (`" . implode('`, `', $names) . "`) VALUES " . implode(', ', $sql_rows);
        return $this->query($sql);
    }

    public function update(string $table, arrow $rows, string ...$cond)
    {
        $fields = [];
        $args = [];
        foreach ($rows as $row_name => $row_value) {
            $fields[] = "`{$row_name}`=?";
            $args[] = $row_value;
        }
        $sql = "UPDATE `$table` SET " . implode(', ', $fields);
        if (!empty($cond)) {
            $sql .= " WHERE " . $cond[0];
            if (count($cond) > 1)
                $args = array_merge($args, array_slice($cond, 1));
        }
        return $this->query($sql, ...$args);
    }

    public function fetch(mysqli_result $q)
    {
        $row = $q->fetch_assoc();
        if (!$row) {
            $q->free();
            return false;
        }
        return $row;
    }

    public function result(mysqli_result $q, int $field = 0)
    {
        return $q ? $q->fetch_row()[$field] : false;
    }

    public function insertId()
    {
        return $this->link->insert_id;
    }

    public function numRows(mysqli_result $query): int
    {
        return $query->num_rows;
    }

    public function escape(string $s): string
    {
        return $this->link->real_escape_string($s);
    }

}
