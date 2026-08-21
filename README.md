# Statamic Password Protect

Site-wide password protection for Statamic 6. Anonymous visitors must enter a password to view the site. Logged-in CP users always bypass the wall.

Settings are managed through the addon settings page in the Statamic control panel.

## Requirements

- PHP 8.3+
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
4. Optionally set a **Title** for the password page (defaults to your site name)
5. Save

That's it. Anonymous visitors will now see a password prompt. Authenticated CP users are never affected.

## How It Works

A middleware intercepts frontend requests and Statamic's REST API, GraphQL, and HTTP-served Glide routes. It checks (in order):

1. Is password protection enabled with a password set? If not, pass through.
2. Is this the exact CP route or one of its child segments? Pass through.
3. Is this the password form itself? Pass through.
4. Is the visitor logged into Statamic with the `access cp` permission? Pass through.
5. Does the session or HttpOnly authorization cookie contain a fingerprint for the current password and authorization generation? Pass through.
6. Otherwise, redirect to the password form.

After entering the correct password, visitors are redirected back to the page they originally requested. Authorization persists for the browser session. Changing the password or disabling and re-enabling protection invalidates every existing authorization.

Protected responses are marked `private, no-store` so reverse proxies and browsers do not reuse an authorized response for another visitor.

Passwords are stored as one-way hashes. Existing plaintext settings from earlier addon versions are migrated automatically the next time the addon boots. The password hash is never preloaded into the control panel form.

Password verification is limited to five attempts per IP address per minute, with an additional global limit of 100 attempts per minute.

## Static Caching

When password protection is enabled, the addon clears Statamic's existing static cache before disabling the static caching strategy. This removes previously generated full-measure files before nginx or Apache can serve them and ensures new requests pass through the middleware.

When you disable password protection, the original static caching strategy is restored. This also works with long-running application workers.

Files that are deliberately exposed directly from `public/` are outside PHP and cannot be protected by this addon. Keep sensitive source assets outside public storage and serve them through a protected application route or authenticated storage layer.

**SSG (Static Site Generator):** This addon is not compatible with `statamic/ssg`. If you're generating a fully static site, password protection must be handled at the web server level (e.g., HTTP Basic Auth via nginx/Apache).

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
