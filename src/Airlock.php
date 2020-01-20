<?php

namespace Laravel\Airlock;

use Laravel\Airlock\HasApiTokens;

class Airlock
{
    /**
     * The personal access client model class name.
     *
     * @var string
     */
    public static $personalAccessTokenModel = 'Laravel\\Airlock\\PersonalAccessToken';

    /**
     * Indicates if Airlock's migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Set the personal access token model name.
     *
     * @param  string  $model
     * @return void
     */
    public static function usePersonalAccessTokenModel($model)
    {
        static::$personalAccessTokenModel = $model;
    }

    /**
     * Determine if Airlock's migrations should be run.
     *
     * @return bool
     */
    public static function shouldRunMigrations()
    {
       return static::$runsMigrations;
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
