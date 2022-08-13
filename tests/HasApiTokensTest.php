<?php

namespace Laravel\Sanctum\Tests;

use Illuminate\Support\Carbon;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use Orchestra\Testbench\TestCase;

class HasApiTokensTest extends TestCase
{
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

    public function test_tokens_can_be_created_with_custom_length()
    {
        config(['sanctum.token_length' => 140]);

        $class = new ClassThatHasApiTokens;

        $newToken = $class->createToken('test');

        [$id, $token] = explode('|', $newToken->plainTextToken);

        $this->assertTrue(config('sanctum.token_length') == strlen($token));
    }

    public function test_can_check_token_abilities()
    {
        $class = new ClassThatHasApiTokens;

        $class->withAccessToken(new TransientToken);

        $this->assertTrue($class->tokenCan('foo'));
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
