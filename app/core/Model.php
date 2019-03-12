<?php

/**
 * Core model class.
 *
 * Implements active record pattern by interacting with DB class.
 */
class Model
{
    /** @var string $table Name of database table corrseponding to the model. */
    protected static $table;

    /** @var string $table Name of primary key in table. */
    protected static $primaryKey = 'id';

    /** @var array $fillable List of fields in the database table which queries can insert/update.
     * Certain fields may need to be excluded if the database updates
     * them automatically, such as an auto-incrementing ID or a timestamp.
     */
    protected static $fillable = [];


    /**
     * Retrieve record(s) from database by their primary key
     * and return as (a) new model instance(s).
     *
     * @return mixed Record(s)
     */
    public static function find($values)
    {
        if (!is_array($values)) {
            return DB::table(static::$table)->return(get_called_class())->where(static::$primaryKey, $values)->first();
        }
        $orWhere = [];
        foreach ($values as $value) {
            $orWhere[] = [static::$primaryKey, $value];
        }
        return DB::table(static::$table)->return(get_called_class())->orWhere($orWhere)->get();
    }


    /**
     * Retrieve all records from database and instantiate as a new model object.
     *
     * @return array Record(s)
     */
    public static function all()
    {
        return DB::table(static::$table)->return(get_called_class())->get();
    }


    /**
     * Add where clause to query builder and set it to
     * return instances of the model.
     *
     * @return DB Query builder instance
     */
    public static function where(...$params)
    {
        return DB::table(static::$table)->return(get_called_class())->where(...$params);
    }


    /**
     * Delete record(s) from the database.
     *
     * @return int Number of records deleted.
     */
    public static function destroy($values)
    {
        if (!is_array($values)) {
            return DB::table(static::$table)->return(get_called_class())->where(static::$primaryKey, $values)->limit(1)->delete();
        }
        $orWhere = [];
        foreach ($values as $value) {
            $orWhere[] = [static::$primaryKey, $value];
        }
        return DB::table(static::$table)->return(get_called_class())->orWhere($orWhere)->limit(count($values))->delete();
    }


    /**
     * Insert a record into the database.
     *
     * @return mixed Primary key of last inserted record, or false if insert failed.
     */
    public static function create(array $values)
    {
        $vals = array_filter($values, function($key) {
            return in_array($key, static::$fillable, true);
        }, ARRAY_FILTER_USE_KEY);
        if ($key = DB::table(static::$table)->insert($vals)) {
            return static::find($key);
        }
        return false;
    }


    /**
     * Save object as a record in the database.
     *
     * @return bool True if record successfully updated/inserted; false otherwise.
     */
    public function save()
    {
        $primaryKey = self::$primaryKey;

        if (isset($this->$primaryKey)) {
            return $this->update();
        }
        return $this->insert();
    }


    /**
     * Save object as a new record in the database base on its primary key.
     *
     * @return bool True if record successfully inserted; false otherwise.
     */
    protected function insert()
    {
        $attributes = $this->getAttributes();

        $primaryKey = self::$primaryKey;

        if ($result = DB::table(static::$table)->insert($attributes)) {
            $this->$primaryKey = $result;
            return true;
        }

        return false;
    }


    /**
     * Update existing record in the database base on its primary key.
     *
     * @return bool True if record updated or change had no effect; false otherwise.
     */
    protected function update()
    {
        $attributes = $this->getAttributes();

        $primaryKey = static::$primaryKey;

        $result = DB::table(static::$table)->where($primaryKey, $this->$primaryKey)->limit(1)->update($attributes);
        return $result !== false;
    }


    /**
     * Delete record from database base on its primary key.
     *
     * @return bool True if record successfully deleted; false otherwise.
     */
    public function delete()
    {
        $primaryKey = static::$primaryKey;
        return (bool) DB::table(static::$table)->where($primaryKey, $this->$primaryKey)->limit(1)->delete();
    }


    /**
     * Get associative array of all attributes on the current object
     * which correspond to a fillable field in the database table.
     *
     * @return array Array of fillable attributes.
     */
    protected function getAttributes()
    {
        $attributes = [];
        foreach (static::$fillable as $field) {
            if (property_exists($this, $field)) {
                $attributes[$field] = $this->$field;
            }
        }
        return $attributes;
    }


    /**
     * Assign values from an associative array or object to this model instance.
     *
     * @return object Model instance with values assigned.
     */
    public function assign($values)
    {
        foreach ($values as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }
}
