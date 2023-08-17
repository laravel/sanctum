<?php

namespace Laravel\Sanctum\Tests\Unit;

use Laravel\Sanctum\TransientToken;
use PHPUnit\Framework\TestCase;

class TransientTokenTest extends TestCase
{
    public function test_can_determine_what_it_can_and_cant_do()
    {
        $token = new TransientToken;

        $this->assertTrue($token->can('foo'));
        $this->assertTrue($token->can('bar'));
        $this->assertFalse($token->cant('foo'));
        $this->assertFalse($token->cant('bar'));
    }
}
