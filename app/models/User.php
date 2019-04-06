<?php

class User extends Model
{
    protected static $table = 'users';
    protected static $fillable = ['first_name', 'last_name', 'email', 'password'];

    public function isAdmin()
    {
        return (int) $this->is_admin === 1;
    }


    /**
     * Authorization helper.
     *
     * @param string $method Name of method to run on relevant policy class.
     * @param mixed $model Model instance, or name of model class.
     * @return bool True if authorized; false otherwise.
     * @throws Exception Exception thrown if policy not found.
     */
    public function can(string $method, $model)
    {
        return can($method, $model, $this);
    }
}
