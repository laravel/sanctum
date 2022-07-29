# Upgrade Guide

## Upgrading To 3.0 From 2.x

### Minimum Versions

The following required dependency versions have been updated:

- The minimum PHP version is now v8.0.2
- The minimum Laravel version is now v9.21

### New `expired_at` Column

Sanctum now supports expiring tokens individually. For this to work, a new `expired_at` column needs to be added to your `personal_access_tokens` table. Create a migration in your app with the following schema change:

```php
Schema::table('personal_access_tokens', function (Blueprint $table) {
    $table->timestamp('expires_at')->nullable()->after('last_used_at');
});
```

Running this migration requires you to [install the `doctrine/dbal` package](https://laravel.com/docs/migrations#renaming-columns).
