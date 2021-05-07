<?php

abstract class model {

    const DB_TABLE = null;
    const DB_KEY = 'id';

    const STRING     = 0;
    const INTEGER    = 1;
    const FLOAT      = 2;
    const ARRAY      = 3;
    const BOOLEAN    = 4;
    const JSON       = 5;
    const SERIALIZED = 6;

    protected static $Fields = [];

    public static function create_instance(...$args) {
        $cl = get_called_class();
        return new $cl(...$args);
    }

    public function __construct($raw) {
        foreach (static::$Fields as $name => $type)
            $this->{toCamelCase($name)} = self::cast_to_type($type, $raw[$name]);

        if (is_null(static::DB_TABLE))
            trigger_error('class '.get_class($this).' doesn\'t have DB_TABLE defined');
    }

    /**
     * @param $fields
     */
    public function edit($fields) {
        $db = getMySQL();

        $save = [];
        foreach ($fields as $name => $value) {
            switch (static::$Fields[$name]) {
                case self::ARRAY:
                    if (is_array($value)) {
                        $fields[$name] = implode(',', $value);
                        $save[$name] = $value;
                    }
                    break;

                case self::INTEGER:
                    $value = (int)$value;
                    $fields[$name] = $value;
                    $save[$name] = $value;
                    break;

                case self::FLOAT:
                    $value = (float)$value;
                    $fields[$name] = $value;
                    $save[$name] = $value;
                    break;

                case self::BOOLEAN:
                    $fields[$name] = $value ? 1 : 0;
                    $save[$name] = $value;
                    break;

                case self::JSON:
                    $fields[$name] = jsonEncode($value);
                    $save[$name] = $value;
                    break;

                case self::SERIALIZED:
                    $fields[$name] = serialize($value);
                    $save[$name] = $value;
                    break;

                default:
                    $value = (string)$value;
                    $fields[$name] = $value;
                    $save[$name] = $value;
                    break;
            }
        }

        if (!$db->update(static::DB_TABLE, $fields, static::DB_KEY."=?", $this->get_id())) {
            //debugError(__METHOD__.': failed to update database');
            return;
        }

        foreach ($save as $name => $value)
            $this->{toCamelCase($name)} = $value;
    }

    /**
     * @return int
     */
    public function get_id() {
        return $this->{toCamelCase(static::DB_KEY)};
    }

    /**
     * @param array $fields
     * @param array $custom_getters
     * @return array
     */
    public function as_array(array $fields = [], array $custom_getters = []) {
        if (empty($fields))
            $fields = array_keys(static::$Fields);

        $array = [];
        foreach ($fields as $field) {
            if (isset($custom_getters[$field]) && is_callable($custom_getters[$field])) {
                $array[$field] = $custom_getters[$field]();
            } else {
                $array[$field] = $this->{toCamelCase($field)};
            }
        }

        return $array;
    }

    /**
     * @param $type
     * @param $value
     * @return array|bool|false|float|int|string
     */
    protected static function cast_to_type($type, $value) {
        switch ($type) {
            case self::BOOLEAN:
                return (bool)$value;

            case self::INTEGER:
                return (int)$value;

            case self::FLOAT:
                return (float)$value;

            case self::ARRAY:
                return array_filter(explode(',', $value));

            case self::JSON:
                return jsonDecode($value);

            case self::SERIALIZED:
                return unserialize($value);

            default:
                return (string)$value;
        }
    }

}
