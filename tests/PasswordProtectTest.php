<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Noo\PasswordProtect\Fieldtypes\ProtectedPassword;
use Noo\PasswordProtect\Http\Middleware\PasswordProtect;
use Noo\PasswordProtect\Security\PasswordAccess;
use Noo\PasswordProtect\Security\PasswordSettings;
use Noo\PasswordProtect\Security\StaticCacheGuard;
use Rebing\GraphQL\GraphQLController;
use Statamic\Events\AddonSettingsSaving;
use Statamic\Facades\Addon;
use Statamic\Facades\User;
use Statamic\Http\Controllers\GlideController;
use Statamic\StaticCaching\StaticCacheManager;

const VALID_PASSWORD = 'x';

function enablePasswordProtection(string $password = VALID_PASSWORD): void
{
    $settings = Addon::get(PasswordSettings::ADDON)->settings();
    $settings->set('enabled', true);
    $settings->set('password', $password);
    $settings->save();
}

function disablePasswordProtection(): void
{
    $settings = Addon::get(PasswordSettings::ADDON)->settings();
    $settings->set('enabled', false);
    $settings->save();
}

it('redirects anonymous visitors to the password form', function () {
    enablePasswordProtection();

    $this->get('/about')
        ->assertRedirect(route('statamic.password-protect.show'));
});

it('shows the password form', function () {
    enablePasswordProtection();

    $this->get(route('statamic.password-protect.show'))
        ->assertOk()
        ->assertViewIs('statamic-password-protect::password');
});

it('does not impose password restrictions', function () {
    $rules = Addon::get(PasswordSettings::ADDON)
        ->settingsBlueprint()
        ->field('password')
        ->rules()['password'];

    expect($rules)->toBe(['required_if:enabled,true']);
});

it('stores passwords as one-way hashes', function () {
    enablePasswordProtection();

    $stored = Addon::get(PasswordSettings::ADDON)->settings()->raw()['password'];

    expect($stored)->not->toBe(VALID_PASSWORD)
        ->and(password_verify(VALID_PASSWORD, $stored))->toBeTrue();
});

it('migrates a legacy plaintext password', function () {
    Event::fakeFor(function () {
        $settings = Addon::get(PasswordSettings::ADDON)->settings();
        $settings->set('enabled', true);
        $settings->set('password', VALID_PASSWORD);
        $settings->save();
    }, [AddonSettingsSaving::class]);

    app(PasswordSettings::class)->migrateLegacyPassword();

    $stored = Addon::get(PasswordSettings::ADDON)->settings()->raw()['password'];

    expect(password_verify(VALID_PASSWORD, $stored))->toBeTrue();
});

it('migrates a legacy plaintext password that resembles a password hash', function () {
    $hashShapedPassword = Hash::make('not-the-site-password');

    Event::fakeFor(function () use ($hashShapedPassword) {
        $settings = Addon::get(PasswordSettings::ADDON)->settings();
        $settings->set('enabled', true);
        $settings->set('password', $hashShapedPassword);
        $settings->set('password_hashed', false);
        $settings->save();
    }, [AddonSettingsSaving::class]);

    app(PasswordSettings::class)->migrateLegacyPassword();

    $stored = Addon::get(PasswordSettings::ADDON)->settings()->raw()['password'];

    expect($stored)->not->toBe($hashShapedPassword)
        ->and(password_verify($hashShapedPassword, $stored))->toBeTrue();
});

it('does not expose the stored hash in the control panel field', function () {
    enablePasswordProtection();
    $stored = Addon::get(PasswordSettings::ADDON)->settings()->raw()['password'];
    $fieldtype = new ProtectedPassword;
    $user = User::make()
        ->email('admin@example.com')
        ->makeSuper();
    $user->save();

    expect($fieldtype->preProcess($stored))->toBe(ProtectedPassword::UNCHANGED);

    try {
        $this->actingAs($user)
            ->get(cp_route('addons.settings.edit', 'statamic-password-protect'))
            ->assertOk()
            ->assertDontSee($stored);
    } finally {
        $user->delete();
    }
});

it('keeps an unchanged hashed password when settings are saved', function () {
    enablePasswordProtection();
    $settings = Addon::get(PasswordSettings::ADDON)->settings();
    $stored = $settings->raw()['password'];

    $settings->set('title', 'Updated title');
    $settings->set('password', ProtectedPassword::UNCHANGED);
    $settings->save();

    expect(Addon::get(PasswordSettings::ADDON)->settings()->raw()['password'])->toBe($stored);
});

it('rejects an incorrect password', function () {
    enablePasswordProtection();

    $this->post(route('statamic.password-protect.verify'), [
        'password' => 'wrong-password',
    ])->assertRedirect()
        ->assertSessionHasErrors('password');
});

it('throttles repeated password guesses', function () {
    enablePasswordProtection();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('statamic.password-protect.verify'), [
            'password' => 'wrong-password-'.$attempt,
        ])->assertRedirect();
    }

    $this->post(route('statamic.password-protect.verify'), [
        'password' => 'sixth-wrong-password',
    ])->assertTooManyRequests();
});

it('accepts the correct password and issues session and cookie authorization', function () {
    enablePasswordProtection();

    $this->post(route('statamic.password-protect.verify'), [
        'password' => VALID_PASSWORD,
    ])->assertRedirect('/')
        ->assertCookie(PasswordAccess::COOKIE);

    $this->get('/about')
        ->assertSessionMissing('errors')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertStatus(404);
});

it('accepts a password longer than the previous maximum', function () {
    $password = str_repeat('x', 1025);
    enablePasswordProtection($password);

    $this->post(route('statamic.password-protect.verify'), [
        'password' => $password,
    ])->assertRedirect('/')
        ->assertCookie(PasswordAccess::COOKIE);
});

it('supports authorization on routes without a session middleware', function () {
    enablePasswordProtection();
    $hash = app(PasswordSettings::class)->passwordHash();
    $salt = app(PasswordSettings::class)->authorizationSalt();
    $access = app(PasswordAccess::class);
    $request = Request::create('/api/sensitive', 'GET', [], [
        PasswordAccess::COOKIE => $access->fingerprint($hash, $salt),
    ]);

    $response = app(PasswordProtect::class)->handle($request, fn () => response('sensitive'));

    expect($response->getContent())->toBe('sensitive');
});

it('revokes existing authorization when the password changes', function () {
    enablePasswordProtection('old password that is long enough');

    $this->post(route('statamic.password-protect.verify'), [
        'password' => 'old password that is long enough',
    ])->assertRedirect('/');

    enablePasswordProtection('new password that is long enough');

    $this->get('/about')
        ->assertRedirect(route('statamic.password-protect.show'));
});

it('revokes existing authorization after disable and re-enable', function () {
    enablePasswordProtection();

    $this->post(route('statamic.password-protect.verify'), [
        'password' => VALID_PASSWORD,
    ])->assertRedirect('/');

    disablePasswordProtection();

    $settings = Addon::get(PasswordSettings::ADDON)->settings();
    $settings->set('enabled', true);
    $settings->save();

    $this->get('/about')
        ->assertRedirect(route('statamic.password-protect.show'));
});

it('does not grant authorization while protection is disabled', function () {
    $settings = Addon::get(PasswordSettings::ADDON)->settings();
    $settings->set('enabled', false);
    $settings->set('password', VALID_PASSWORD);
    $settings->save();

    $this->post(route('statamic.password-protect.verify'), [
        'password' => VALID_PASSWORD,
    ])->assertSessionHasErrors('password')
        ->assertCookieMissing(PasswordAccess::COOKIE);
});

it('does not accept the legacy boolean session authorization flag', function () {
    enablePasswordProtection();

    $this->withSession(['password_protect_authorized' => true])
        ->get('/about')
        ->assertRedirect(route('statamic.password-protect.show'));
});

it('lets users with CP access through without a password', function () {
    enablePasswordProtection();

    $user = User::make()
        ->email('admin@example.com')
        ->makeSuper();

    $this->actingAs($user)->get('/about')
        ->assertStatus(404);
});

it('uses the configured CP guard for authenticated bypasses', function () {
    enablePasswordProtection();

    config([
        'auth.guards.password-protect-cp' => config('auth.guards.web'),
        'statamic.users.guards.cp' => 'password-protect-cp',
    ]);

    $user = User::make()
        ->email('custom-guard-admin@example.com')
        ->makeSuper();

    $this->actingAs($user, 'password-protect-cp')->get('/about')
        ->assertStatus(404);
});

it('does not let authenticated users without CP access bypass protection', function () {
    enablePasswordProtection();

    $user = User::make()->email('frontend@example.com');
    $user->save();

    try {
        expect($user->can('access cp'))->toBeFalse();

        $this->actingAs($user)->get('/about')
            ->assertRedirect(route('statamic.password-protect.show'));
    } finally {
        $user->delete();
    }
});

it('does not redirect when protection is disabled', function () {
    disablePasswordProtection();

    $response = $this->get('/about');

    expect($response->headers->get('Location'))
        ->not->toBe(route('statamic.password-protect.show'));
});

it('does not redirect when no password is set', function () {
    $settings = Addon::get(PasswordSettings::ADDON)->settings();
    $settings->set('enabled', true);
    $settings->set('password', '');
    $settings->save();

    $response = $this->get('/about');

    expect($response->headers->get('Location'))
        ->not->toBe(route('statamic.password-protect.show'));
});

it('does not protect exact CP routes', function () {
    enablePasswordProtection();

    $response = $this->get('/'.config('statamic.cp.route', 'cp'));

    expect($response->headers->get('Location'))
        ->not->toBe(route('statamic.password-protect.show'));
});

it('does protect frontend paths that merely start with the CP route', function (string $path) {
    enablePasswordProtection();

    $this->get($path)
        ->assertRedirect(route('statamic.password-protect.show'));
})->with(['/cpanel', '/cp-2024-recap', '/cpd-training']);

it('protects frontend live preview URLs', function () {
    enablePasswordProtection();

    $this->get('/about?live-preview=entry-id&token=preview-token')
        ->assertRedirect(route('statamic.password-protect.show'));
});

it('attaches protection to REST, GraphQL, and Glide delivery routes', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());
    $routeGroups = [
        'REST' => $routes->filter(fn ($route) => str_starts_with($route->getActionName(), 'Statamic\\Http\\Controllers\\API\\')),
        'GraphQL' => $routes->filter(fn ($route) => str_starts_with($route->getActionName(), GraphQLController::class.'@')),
        'Glide' => $routes->filter(fn ($route) => str_starts_with($route->getActionName(), GlideController::class.'@')),
    ];

    foreach ($routeGroups as $name => $group) {
        expect($group, $name.' routes should be registered')->not->toBeEmpty();

        $group->each(function ($route) use ($name) {
            event(new RouteMatched($route, Request::create('/')));

            $middleware = $route->gatherMiddleware();

            expect(
                $middleware,
                $name.' route '.$route->getActionName().' should be protected',
            )->toContain(PasswordProtect::class)
                ->and(array_slice($route->middleware(), 0, 4))->toBe([
                    EncryptCookies::class,
                    AddQueuedCookiesToResponse::class,
                    StartSession::class,
                    PasswordProtect::class,
                ]);
        });
    }
});

it('blocks anonymous requests to REST, GraphQL, and Glide before their controllers run', function () {
    enablePasswordProtection();

    $routes = collect(app('router')->getRoutes()->getRoutes());
    $routesToCheck = [
        $routes->first(fn ($route) => str_starts_with($route->getActionName(), 'Statamic\\Http\\Controllers\\API\\')),
        $routes->first(fn ($route) => str_starts_with($route->getActionName(), GraphQLController::class.'@')),
        $routes->first(fn ($route) => str_starts_with($route->getActionName(), GlideController::class.'@')),
    ];

    foreach ($routesToCheck as $route) {
        $uri = '/'.preg_replace('/\{[^}]+\??\}/', 'security-review', $route->uri());
        $method = in_array('GET', $route->methods(), true) ? 'GET' : $route->methods()[0];

        $this->call($method, $uri)
            ->assertRedirect(route('statamic.password-protect.show'));
    }
});

it('flushes static caching once per protected password and restores the strategy', function () {
    config(['statamic.static_caching.strategy' => 'full']);

    $staticCache = Mockery::mock(StaticCacheManager::class);
    $staticCache->shouldReceive('flush')->once();
    $cache = new CacheRepository(new ArrayStore);
    $guard = new StaticCacheGuard($staticCache, $cache);

    $guard->synchronize(true, 'fingerprint');
    expect(config('statamic.static_caching.strategy'))->toBeNull();

    $guard->synchronize(true, 'fingerprint');
    $guard->synchronize(false);

    expect(config('statamic.static_caching.strategy'))->toBe('full');
});
