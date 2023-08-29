<?php

namespace Laravel\Sanctum\Tests\Feature;

use Illuminate\Support\Carbon;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class HasApiTokensTest extends TestCase
{
    use WithWorkbench;

    public function test_tokens_can_be_created()
    {
        $class = new ClassThatHasApiTokens;
        $time = Carbon::now();

        $newToken = $class->createToken('test', ['foo'], $time);

        [$id, $token] = explode('|', $newToken->plainTextToken);

        $this->assertEquals(
            $newToken->accessToken->token,
            hash('sha256', $token)
        );

        $this->assertEquals(
            $newToken->accessToken->id,
            $id
        );

        $this->assertEquals(
            $time->toDateTimeString(),
            $newToken->accessToken->expires_at->toDateTimeString()
        );
    }

    public function test_can_check_token_abilities()
    {
        $class = new ClassThatHasApiTokens;

        $class->withAccessToken(new TransientToken);

        $this->assertTrue($class->tokenCan('foo'));
    }

    public function test_token_checksum_is_valid()
    {
        $config = require __DIR__.'/../../config/sanctum.php';
        $this->app['config']->set('sanctum.token_prefix', $config['token_prefix']);

        $class = new ClassThatHasApiTokens;

        $newToken = $class->createToken('test', ['foo']);

        [$id, $token] = explode('|', $newToken->plainTextToken);
        $splitToken = explode('_', $token);
        $tokenEntropy = substr(end($splitToken), 0, -8);

        $this->assertEquals(
            hash('crc32b', $tokenEntropy),
            substr($token, -8)
        );
    }
}

class ClassThatHasApiTokens implements HasApiTokensContract
{
    use HasApiTokens;

    public function tokens()
    {
        return new class
        {
            public function create(array $attributes)
            {
                return new PersonalAccessToken($attributes);
            }
        };
    }
}
