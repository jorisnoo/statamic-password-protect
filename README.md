# Statamic Password Protect

Site-wide password protection for Statamic 6. Anonymous visitors must enter a password to view the site. Logged-in CP users always bypass the wall.

Settings are managed through the addon settings page in the Statamic control panel.

## Requirements

- PHP 8.4+
- Statamic 6.24.2+
- Laravel 12.40+ or 13

## Installation

```bash
composer require jorisnoo/statamic-password-protect
```

## Configuration

1. Go to **CP > Addons > Password Protect > Settings**
2. Toggle **Enabled** on
3. Set a **Password**
4. Optionally set a **Title** for the password page (leave blank to hide it)
5. Save

That's it. Anonymous visitors will now see a password prompt. Authenticated CP users are never affected.

## How It Works

A middleware intercepts frontend requests and Statamic's REST API, GraphQL, and HTTP-served Glide routes. It checks (in order):

1. Is password protection enabled with a password set? If not, pass through.
2. Is this the exact CP route or one of its child segments? Pass through.
3. Is this the password form itself? Pass through.
4. Is the visitor logged into Statamic with the `access cp` permission? Pass through.
5. Does the session or HttpOnly authorization cookie contain a fingerprint for the current password and authorization generation? Pass through.
6. Otherwise, return HTTP 401 with JSON for REST, GraphQL, and clients requesting JSON; redirect HTML visitors to the password form. The named form route is `statamic.password-protect.show` (normally `/!/statamic-password-protect/password`). Unrelated routes in the application's `api` middleware group are not covered.

After entering the correct password, visitors are redirected to the home page. Authorization persists for the browser session. Changing the password or disabling and re-enabling protection invalidates every existing authorization.

Protected responses are marked `private, no-store` so reverse proxies and browsers do not reuse an authorized response for another visitor.

Passwords are stored as one-way hashes. Legacy plaintext settings still work without writing to disk on boot or login. Save the addon settings once to hash a legacy password. The control panel shows only a mask.

Five failed guesses per minute are allowed per IPv4 address or IPv6 /64. Successful logins do not use another attempt and one network cannot exhaust a shared site-wide limit. Behind a reverse proxy, configure Laravel's trusted proxies so the app sees each client's address.

## Static Caching

Enabling protection clears the configured Statamic static cache. Protected frontend responses opt out of Statamic's cache writer without changing the site's configuration. Turning protection off allows the configured strategy to cache public responses again.

If you deploy settings outside the CP, drain existing requests and clear static files on **each** serving node before taking it back online. PHP cannot remove a response already held by an external CDN: purge that cache separately when enabling the wall.

Files that are deliberately exposed directly from `public/` are outside PHP and cannot be protected by this addon. Keep sensitive source assets outside public storage and serve them through a protected application route or authenticated storage layer.

**SSG (Static Site Generator):** This addon is not compatible with `statamic/ssg`. If you're generating a fully static site, password protection must be handled at the web server level (e.g., HTTP Basic Auth via nginx/Apache).

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Maintainer releases

See [RELEASING.md](RELEASING.md) for versioning, changelog entries and the release command.
