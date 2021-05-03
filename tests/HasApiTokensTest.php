<?php

namespace Laravel\Sanctum\Tests;

use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use PHPUnit\Framework\TestCase;

class HasApiTokensTest extends TestCase
{
    public function test_tokens_can_be_created()
    {
        $class = new ClassThatHasApiTokens;

        $newToken = $class->createToken('test', ['foo']);

        [$id, $token] = explode('|', $newToken->plainTextToken);

        $this->assertEquals(
            $newToken->accessToken->token,
            hash('sha256', $token)
        );

        $this->assertEquals(
            $newToken->accessToken->id,
            $id
        );
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
