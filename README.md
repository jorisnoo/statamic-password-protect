# Statamic Password Protect

Site-wide password protection for Statamic 6. Anonymous visitors must enter a password to view the site. Logged-in CP users always bypass the wall.

Settings are managed through the Statamic control panel under the addon settings page.

## Requirements

- PHP 8.3+
- Statamic 6
- Laravel 12

## Installation

```bash
composer require jorisnoo/statamic-password-protect
```

## Configuration

1. Go to **CP > Tools > Addons > Password Protect > Settings**
2. Toggle **Enabled** on
3. Set a **Password**
4. Save

That's it. Anonymous visitors will now see a password prompt. Authenticated CP users are never affected.

## How It Works

A middleware on the `web` group intercepts every frontend request. It checks (in order):

1. Is password protection enabled with a password set? If not, pass through.
2. Is this a CP route? Pass through.
3. Is this the password form itself? Pass through.
4. Is the visitor logged into Statamic? Pass through.
5. Does the session have a valid authorization flag? Pass through.
6. Otherwise, redirect to the password form.

After entering the correct password, visitors are redirected back to the page they originally requested. The session flag persists for the duration of the browser session.

## Static Caching

When password protection is enabled, the addon automatically disables Statamic's static caching strategy. This ensures every request goes through the middleware and anonymous visitors cannot access cached pages.

When you disable password protection, static caching resumes normally.

**SSG (Static Site Generator):** This addon is not compatible with `statamic/ssg`. If you're generating a fully static site, password protection must be handled at the web server level (e.g., HTTP Basic Auth via nginx/Apache).

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
