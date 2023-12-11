# Upgrade Guide

## Upgrading To 4.0 From 3.x

### Migration Changes

Sanctum 4.0 no longer automatically loads migrations from its own migrations directory. Instead, you should run the following command to publish Sanctum's migrations to your application:

```bash
php artisan vendor:publish --tag=sanctum-migrations
```

### Minimum PHP Version

PHP 8.2 is now the minimum required version.

### Minimum Laravel Version

Laravel 11.0 is now the minimum required version.

