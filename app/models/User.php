<?php

/**
 * User model.
 */
class User extends Model
{
    protected static $table = 'users';
    protected static $fillable = ['email', 'password'];
}
