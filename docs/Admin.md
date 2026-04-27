# Admin Backend

The plugin ships with a self-contained admin backend that gives you health, abuse-signal, and maintenance views for the captchas table without dropping into SQL.

## Routing

By default the admin mounts at `/admin/captcha/` (controlled by two `Configure` keys):

```php
// config/app.php (or wherever you keep Captcha config)
'Captcha' => [
    'adminPrefix' => 'Admin',     // Route prefix
    'adminRoutePath' => '/captcha', // Path under the prefix
    // ...
],
```

Both keys are optional — leave them at the defaults and you get `/admin/captcha/`.

## Authorization (deny by default)

The admin backend is **deny by default**. You must register an access closure that returns `true` for the requests you want to let through. The closure receives the current `ServerRequest` so you can inspect identity, roles, IP, headers, anything.

```php
// config/bootstrap.php (or your Application::bootstrap())
use Cake\Http\ServerRequest;
use Cake\Core\Configure;

Configure::write('Captcha.adminAccess', function (ServerRequest $request): bool {
    $identity = $request->getAttribute('identity');
    return $identity !== null && in_array('admin', (array)($identity->roles ?? []), true);
});
```

If `Captcha.adminAccess` is not set, every admin URL responds with `403 Forbidden`.

This deliberately diverges from sister plugins (cakephp-queue, CakePHP-DatabaseLog) which assume the host app secures the prefix. Captcha is security-adjacent and accidental exposure is harmful, so a closure is required.

## Layout

The admin uses a self-contained Bootstrap 5 layout shipped with the plugin (`Captcha.captcha-admin`). To use your host app's layout instead, set:

```php
'Captcha' => [
    'adminLayout' => false,         // host layout
    // or 'adminLayout' => 'MyApp.admin',
],
```

## Features

### Dashboard (`/admin/captcha/`)

- **Stat tiles**: Open · Solved (24h) · Failed (24h) · Solve rate · Expired (no attempt) · Throttled now
- **Hourly heatmap**: 7×24 grid colored by issued count, with `solved/failed` breakdown on hover
- **Engine + config snapshot**: which engine is active, plus the load-bearing config values
- **Maintenance strip**: `Run cleanup now`, `Hard reset (truncate)` — both `confirm`-gated `postLink`s

### IPs (`/admin/captcha/ips/`)

- Four leaderboards: Top issued · Top solved · Top failed · Currently rate-limited
- Window selector: 24h / 7d
- Per-row actions: Unblock (clear rate-limit cache for that IP across known sessions) · Delete (remove all captchas for that IP)

### Per-IP detail (`/admin/captcha/ips/view/{ip}`)

- Header tile counts for that IP (24h)
- Paginated table of recent captchas for the IP, with `solved` icon, `created`, `used`, truncated session_id and uuid

### Engine (`/admin/captcha/engine`)

Read-only list of registered engines (`MathEngine`, `NullEngine`, plus any custom one you set in `Captcha.engine`). Active engine highlighted.

### Preview (`/admin/captcha/preview`)

Renders a sample captcha through the configured engine for verification. The preview does **not** create a `captchas` table row — it only invokes the engine's `generate()` method. Useful when sanity-checking a config change without touching a real form.

### Config (`/admin/captcha/config`)

Read-only flat dump of every `Captcha.*` key with its resolved value. Configure isn't runtime-editable, so this is intentionally view-only.

## Data model

The admin reads from the existing `captchas` table plus one new column:

| Column | Type | Why |
|---|---|---|
| `solved` | nullable boolean | `null` = issued/no attempt yet, `true` = correct answer, `false` = wrong answer. Set during verification. |

A migration is shipped:

```
bin/cake migrations migrate -p Captcha
```

Existing rows backfill to `null` (they predate the tracking). Solve-rate computations exclude `null` rows so historical data does not skew the ratio.

## Currently-rate-limited derivation

The verify rate-limit lives in `Cache`, keyed on `sha1(ip|session)` plus a time bucket. Cache keys are not reversible to recover the original IP, and not all cache backends support iteration — so the dashboard derives the throttled-IPs list from the captchas table directly:

```sql
SELECT ip, COUNT(*) AS failed_in_window
FROM captchas
WHERE solved = false
  AND created > NOW() - INTERVAL :window SECOND
GROUP BY ip
HAVING failed_in_window >= :max_failures
```

`:window` and `:max_failures` come from `Captcha.verifyRateLimit`. This is a *good-enough proxy* for the cache state — admins use it to spot patterns and unblock, not as a source of truth.

`Unblock` (the per-IP postLink) re-derives the `(ip, session_id)` tuples from the captchas table and deletes the corresponding cache keys.
