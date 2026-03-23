<?php

namespace Noo\PasswordProtect\Tests;

use Noo\PasswordProtect\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
