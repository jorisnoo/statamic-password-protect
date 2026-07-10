<?php

namespace Noo\PasswordProtect\Listeners;

use Illuminate\Routing\Events\RouteMatched;
use Noo\PasswordProtect\Security\AlternateDeliveryRouteProtector;

class ProtectAlternateDeliveryRoute
{
    public function __construct(private AlternateDeliveryRouteProtector $protector)
    {
    }

    public function handle(RouteMatched $event): void
    {
        $this->protector->protect($event->route);
    }
}
