<?php

namespace Laravel\Airlock;

use Laravel\Airlock\HasApiTokens;

class Airlock
{
    /**
     * The user model that should be used.
     *
     * @var string
     */
    public static $userModel = 'App\\User';

    /**
     * Indicates if Airlock's migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations;

    /**
     * Get the name of the user model used by Airlock.
     *
     * @return string
     */
    public static function userModel()
    {
        return static::$userModel;
    }

    /**
     * Specify the user model that should be used.
     *
     * @param  string  $model
     * @return static
     */
    public static function useUserModel(string $model)
    {
        static::$userModel = $model;

        return new static;
    }

    /**
     * Determine if Airlock's migrations should be run.
     *
     * @return bool
     */
    public static function shouldRunMigrations()
    {
        if (! is_null(static::$runsMigrations)) {
            return static::$runsMigrations;
        }

        $model = config('auth.providers.users.model', static::userModel());

        return class_exists($model)
                    ? in_array(HasApiTokens::class, class_uses_recursive($model))
                    : true;
    }

    /**
     * Configure Airlock to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static;
    }
}
