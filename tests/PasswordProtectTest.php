<?php

use Statamic\Facades\Addon;

function enablePasswordProtection(string $password = 'secret'): void
{
    $settings = Addon::get('jorisnoo/statamic-password-protect')->settings();
    $settings->set('enabled', true);
    $settings->set('password', $password);
    $settings->save();
}

function disablePasswordProtection(): void
{
    $settings = Addon::get('jorisnoo/statamic-password-protect')->settings();
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

it('rejects an incorrect password', function () {
    enablePasswordProtection();

    $this->post(route('statamic.password-protect.verify'), [
        'password' => 'wrong',
    ])->assertRedirect()
        ->assertSessionHasErrors('password');
});

it('accepts the correct password and sets session', function () {
    enablePasswordProtection();

    $this->post(route('statamic.password-protect.verify'), [
        'password' => 'secret',
    ])->assertRedirect('/');

    $this->get('/about')
        ->assertSessionMissing('errors')
        ->assertStatus(404); // 404 because no content exists, but not redirected to password form
});

it('lets authenticated users through without a password', function () {
    enablePasswordProtection();

    $user = \Statamic\Facades\User::make()
        ->email('test@example.com')
        ->makeSuper();

    $response = $this->actingAs($user)->get('/about');

    // Should not redirect to the password form
    expect($response->status())->not->toBe(302);
});

it('does not redirect when protection is disabled', function () {
    disablePasswordProtection();

    $response = $this->get('/about');

    expect($response->headers->get('Location'))
        ->not->toBe(route('statamic.password-protect.show'));
});

it('does not redirect when no password is set', function () {
    $settings = Addon::get('jorisnoo/statamic-password-protect')->settings();
    $settings->set('enabled', true);
    $settings->set('password', '');
    $settings->save();

    $response = $this->get('/about');

    expect($response->headers->get('Location'))
        ->not->toBe(route('statamic.password-protect.show'));
});

it('does not protect cp routes', function () {
    enablePasswordProtection();

    $response = $this->get('/'.config('statamic.cp.route', 'cp'));

    expect($response->headers->get('Location'))
        ->not->toBe(route('statamic.password-protect.show'));
});
