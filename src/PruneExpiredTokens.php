<?php

namespace Laravel\Airlock;

class PruneExpiredTokens
{
    /**
     * Prune the expired tokens.
     *
     * @return void
     */
    public function __invoke()
    {
        $model = Airlock::$personalAccessTokenModel;
        
        $model::where('expires_at', '<=', now())->delete();
    }
}
