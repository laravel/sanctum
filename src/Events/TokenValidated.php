<?php

namespace Laravel\Sanctum\Events;

class TokenValidated
{
    /**
     * The model collection.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $tokenable;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $tokenable
     * @return void
     */
    public function __construct($tokenable)
    {
        $this->tokenable = $tokenable;
    }
}
