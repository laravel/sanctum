<?php

namespace Laravel\Sanctum;

use Exception;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Cookie\CookieValuePrefix;
use Laravel\Sanctum\Events\TokenAuthenticated;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Guard
{
    /**
     * The authentication factory implementation.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The number of minutes tokens should be allowed to remain valid.
     *
     * @var int
     */
    protected $expiration;

    /**
     * The provider name.
     *
     * @var string
     */
    protected $provider;

    /**
     * Create a new guard instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @param  int  $expiration
     * @param  string  $provider
     * @return void
     */
    public function __construct(AuthFactory $auth, Encrypter $encrypter, $expiration = null, $provider = null)
    {
        $this->auth = $auth;
        $this->encrypter = $encrypter;
        $this->expiration = $expiration;
        $this->provider = $provider;
    }

    /**
     * Retrieve the authenticated user for the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function __invoke(Request $request)
    {
        foreach (Arr::wrap(config('sanctum.guard', 'web')) as $guard) {
            if ($user = $this->auth->guard($guard)->user()) {
                return $this->supportsTokens($user)
                    ? $user->withAccessToken(new TransientToken)
                    : $user;
            }
        }

        if($request->cookie(Sanctum::cookie())) {
            if(!$token = $this->getTokenFromCookie($request)) {
                return;
            }

            if ($user = $this->provider->retrieveById($token['sub'])) {
                return $user->withAccessToken(new TransientToken);
            }
        }

        if ($token = $this->getTokenFromRequest($request)) {
            $model = Sanctum::$personalAccessTokenModel;

            $accessToken = $model::findToken($token);

            if (! $this->isValidAccessToken($accessToken) ||
                ! $this->supportsTokens($accessToken->tokenable)) {
                return;
            }

            $tokenable = $accessToken->tokenable->withAccessToken(
                $accessToken
            );

            event(new TokenAuthenticated($accessToken));

            if (method_exists($accessToken->getConnection(), 'hasModifiedRecords') &&
                method_exists($accessToken->getConnection(), 'setRecordModificationState')) {
                tap($accessToken->getConnection()->hasModifiedRecords(), function ($hasModifiedRecords) use ($accessToken) {
                    $accessToken->forceFill(['last_used_at' => now()])->save();

                    $accessToken->getConnection()->setRecordModificationState($hasModifiedRecords);
                });
            } else {
                $accessToken->forceFill(['last_used_at' => now()])->save();
            }

            return $tokenable;
        }
    }

    /**
     * Determine if the tokenable model supports API tokens.
     *
     * @param  mixed  $tokenable
     * @return bool
     */
    protected function supportsTokens($tokenable = null)
    {
        return $tokenable && in_array(HasApiTokens::class, class_uses_recursive(
            get_class($tokenable)
        ));
    }

    /**
     * Get the token from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request)
    {
        if (is_callable(Sanctum::$accessTokenRetrievalCallback)) {
            return (string) (Sanctum::$accessTokenRetrievalCallback)($request);
        }

        return $request->bearerToken();
    }

    /**
     * @param Request $request
     * @return void
     */
    protected function getTokenFromCookie(Request $request)
    {
        try {
            $token = $this->decodeJwtTokenCookie($request);
        } catch (Exception $e) {
            return;
        }

        return $token;

        // TODO: Implement this

//        // We will compare the CSRF token in the decoded API token against the CSRF header
//        // sent with the request. If they don't match then this request isn't sent from
//        // a valid source and we won't authenticate the request for further handling.
//        if (! Passport::$ignoreCsrfToken && (! $this->validCsrf($token, $request) ||
//                time() >= $token['expiry'])) {
//            return;
//        }
//
//        return $token;
    }

    protected function decodeJwtTokenCookie(Request $request)
    {
        // TODO: Encryption key

//        return (array) JWT::decode(
//            CookieValuePrefix::remove($this->encrypter->decrypt($request->cookie(Passport::cookie()), Passport::$unserializesCookies)),
//            new Key(Passport::tokenEncryptionKey($this->encrypter), 'HS256')
//        );

        return (array) JWT::decode(
            CookieValuePrefix::remove($this->encrypter->decrypt($request->cookie(Sanctum::cookie()), false)), new Key('password', 'HS256')
        );
    }

    /**
     * Determine if the provided access token is valid.
     *
     * @param  mixed  $accessToken
     * @return bool
     */
    protected function isValidAccessToken($accessToken): bool
    {
        if (! $accessToken) {
            return false;
        }

        $isValid =
            (! $this->expiration || $accessToken->created_at->gt(now()->subMinutes($this->expiration)))
            && (! $accessToken->expires_at || ! $accessToken->expires_at->isPast())
            && $this->hasValidProvider($accessToken->tokenable);

        if (is_callable(Sanctum::$accessTokenAuthenticationCallback)) {
            $isValid = (bool) (Sanctum::$accessTokenAuthenticationCallback)($accessToken, $isValid);
        }

        return $isValid;
    }

    /**
     * Determine if the tokenable model matches the provider's model type.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $tokenable
     * @return bool
     */
    protected function hasValidProvider($tokenable)
    {
        if (is_null($this->provider)) {
            return true;
        }

        $model = config("auth.providers.{$this->provider}.model");

        return $tokenable instanceof $model;
    }
}
