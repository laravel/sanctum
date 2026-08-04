<?php

namespace Laravel\Sanctum\Tests\Diagnostics\Fixtures;

use Laravel\Sanctum\PersonalAccessToken;

class CustomPersonalAccessToken extends PersonalAccessToken
{
    protected $table = 'custom_personal_access_tokens';
}
