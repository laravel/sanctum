<?php

namespace Laravel\Sanctum\Tests\Unit;

use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\TestCase;

class PersonalAccessTokenTest extends TestCase
{
    public function test_can_determine_what_it_can_and_cant_do()
    {
        $token = new PersonalAccessToken;

        $token->abilities = [];

        $this->assertFalse($token->can('foo'));

        $token->abilities = ['foo'];

        $this->assertTrue($token->can('foo'));
        $this->assertFalse($token->can('bar'));
        $this->assertTrue($token->cant('bar'));
        $this->assertFalse($token->cant('foo'));

        $token->abilities = ['foo', '*'];

        $this->assertTrue($token->can('foo'));
        $this->assertTrue($token->can('bar'));
    }

    public function test_can_uses_strict_comparison_for_abilities()
    {
        $token = new PersonalAccessToken;

        $token->abilities = [true];

        $this->assertFalse($token->can('foo'));
        $this->assertFalse($token->can('*'));
    }
}
