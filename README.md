<p align="center"><img src="https://laravel.com/assets/img/components/logo-airlock.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/airlock"><img src="https://travis-ci.org/laravel/airlock.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/airlock"><img src="https://poser.pugx.org/laravel/airlock/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/airlock"><img src="https://poser.pugx.org/laravel/airlock/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/airlock"><img src="https://poser.pugx.org/laravel/airlock/license.svg" alt="License"></a>
</p>

## Introduction

Laravel Airlock provides a featherweight authentication system for SPAs and simple APIs.

## Installation

You may install Laravel Airlock via Composer:

    composer require laravel/airlock

Next, you should publish the Airlock configuration and migration files using the `vendor:publish` Artisan command. The `airlock` configuration file will be placed in your `config` directory:

    php artisan vendor:publish --provider="Laravel\Airlock\AirlockServiceProvider"

Finally, you should run your database migrations:

    php artisan migrate

## Configuration

### Configuring Your Domains

If you are using Laravel Airlock to authenticate your single page application (SPA), you should configure which domains your SPA will be making requests from. You may configure these domains using the `stateful` configuration option in your `config/airlock.php` configuration file. This configuration setting determines which domains will maintain "stateful" authentication in order to make requests to your API.

### Adding The Airlock Middleware

Next, you should add Airlock's middleware to your `api` middleware group within your `app/Http/Kernel.php` file:

```php
use Laravel\Airlock\Http\Middleware\EnsureFrontendRequestsAreStateful;

'api' => [
    EnsureFrontendRequestsAreStateful::class,
    'throttle:60,1',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### Attaching The Authentication Guard

Next, you should attach the `airlock` authentication guard to your API routes within your `routes/api.php` file. This guard will ensure that incoming requests are authenticated as either a stateful authenticated requests from your SPA or contain a valid API token header if the request is from a third party:

```php
Route::middleware('auth:airlock')->get('/user', function (Request $request) {
    return $request->user();
});
```

If you are using Passport to authenticate other portions of your application using OAuth2, you are welcome to also use Airlock. The `auth` middleware allows you to specify multiple guards that will be used in sequence when attempting to authenticate incoming requests:

```php
Route::middleware('auth:airlock,passport')->get('/user', function (Request $request) {
    return $request->user();
});
```

### SPA Authentication

To authenticate your SPA, your SPA's login page should first make a request to the `/airlock/csrf-cookie` route to initialize CSRF protection for the application:

```js
axios.defaults.withCredentials = true;

axios.get('/airlock/csrf-cookie').then(response => {
    // Login...
});
```

Once CSRF protection has been initialized, you should make a `POST` request to the typical Laravel `/login` route. If the request is successful, you will be authenticated and subsequent requests to your API routes will automatically be authenticated.

#### CORS & Cookies

If you are having trouble authenticating with your application from an SPA that executes on a separate subdomain, you have likely misconfigured your CORS or session cookie settings. You should ensure that your application's CORS configuration is returning the `Access-Control-Allow-Credentials` header with a value of `True`.

In addition, you should ensure your application's session cookie domain configuration supports any subdomain of your root domain. You may do this by prefixing the domain with a leading `.`:

    'domain' => '.domain.com',

## Issuing API Tokens

Airlock also allows you to issue API tokens / personal access tokens that may be used to authenticate API requests. The token should be included in the `Authorization` header as a `Bearer` token.

To begin issuing tokens for users, your `User` model should use the `HasApiTokens` trait:

```php
use Laravel\Airlock\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
}
```

To issue a token, you may use the `createToken` method. The `createToken` method returns a `Laravel\Airlock\NewAccessToken` instance. API tokens are hashed using SHA-256 hashing before being stored in your database, but you may access the plain-text value of the token using the `plainTextToken` property of the `NewAccessToken` instance. You should display this value to the user once:

```php
$token = $user->createToken('token-name');

return $token->plainTextToken;
```

You may access all of the user's tokens using the `tokens` Eloquent relationship provided by the `HasApiTokens` trait:

```php
foreach ($user->tokens as $token) {
    //
}
```

### Token Abilities

Airlock allows you to assign "abilities" to tokens, similar to OAuth "scopes". You may pass an array of string abilities as the second argument to the `createToken` method:

```php
return $user->createToken('token-name', ['server:update'])->plainTextToken;
```

When handling an incoming request authenticated by Airlock, you may determine if the token has a given ability using the `tokenCan` method:

```php
if ($user->tokenCan('server:update')) {
    //
}
```

The `tokenCan` method will always return `true` if the incoming authenticated request was from your first-party SPA.

### Authenticating Mobile Applications

You may use Airlock tokens to authenticate your mobile application's requests to your API. To get started, create a route that accepts the user's email / username, password, and device name, then exchanges them for a new Airlock token. You may then store the token on your device and use it to make additional API requests:

```php
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

Route::post('/airlock/token', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    return $user->createToken($request->device_name)->plainTextToken;
});
```

## Revoking Tokens

You may "revoke" tokens by deleting them from your database using the typical Eloquent methods you are used to:

```php
$user->tokens->each->delete();
```

Within your web application's UI, you may wish to list each of the user's tokens and allow the user to revoke the tokens individually as needed.

## Customization

You may customize the the personal access token model used by Airlock via the `usePersonalAccessTokenModel` methods. Typically, you should call this method from the `boot` method of your `AppServiceProvider`:

```php
use App\Airlock\CustomPersonalAccessToken;
use App\CustomUser;
use Laravel\Airlock\Airlock;

public function boot()
{
    Airlock::usePersonalAccessTokenModel(
        CustomPersonalAccessToken::class
    );
}
```

## Contributing

Thank you for considering contributing to Airlock! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/airlock/security/policy) on how to report security vulnerabilities.

## License

Laravel Airlock is open-sourced software licensed under the [MIT license](LICENSE.md).
