<?php

namespace Laravel\Airlock\Tests;

use Laravel\Airlock\HasApiTokens;
use Laravel\Airlock\PersonalAccessToken;
use Laravel\Airlock\TransientToken;
use Orchestra\Testbench\TestCase;

class HasApiTokensTest extends TestCase
{
    public function test_tokens_can_be_created()
    {
        $class = new ClassThatHasApiTokens;

        $newToken = $class->createToken('test', ['foo']);

        $this->assertEquals(
            $newToken->accessToken->token,
            hash('sha256', $newToken->plainTextToken)
        );
    }

    public function test_can_check_token_abilities()
    {
        $class = new ClassThatHasApiTokens;

        $class->withAccessToken(new TransientToken);

        $this->assertTrue($class->tokenCan('foo'));
    }

    public function test_can_be_created_with_expiration()
    {
        $this->app['config']->set('airlock.expiration', 1);

        $class = new ClassThatHasApiTokens;

        $newToken = $class->createToken('test');

        $this->assertNotEmpty($newToken->accessToken->expires_at);
    }
}

class ClassThatHasApiTokens
{
    use HasApiTokens;

    public function tokens()
    {
        return new class {
            public function create(array $attributes)
            {
                return new PersonalAccessToken($attributes);
            }
        };
    }
}
