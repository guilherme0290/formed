<?php

namespace App\Listeners;

class EventServiceProvider
{
    protected $listen = [
        \Illuminate\Auth\Events\Login::class => [\App\Listeners\UpdateLastLoginAt::class],
    ];

}
