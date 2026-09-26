# Admin Backend

The plugin ships with a self-contained admin backend that gives you health, abuse-signal, and maintenance views for the captchas table without dropping into SQL.

## Routing

By default the admin mounts at `/admin/captcha/` (controlled by optional `Configure` keys):

```php
// config/app.php (or wherever you keep Captcha config)
'Captcha' => [
    'adminPrefix' => 'Admin',     // Controller prefix
    'adminPrefixPath' => null,   // Derive the URL from the prefix name
    'adminRoutePath' => '/captcha', // Path under the prefix
    // ...
],
```

The defaults give `/admin/captcha/`. Set `adminPrefixPath` to `/backend` to mount at
`/backend/captcha/` while keeping the `Admin` controller prefix. `adminRoutePath` controls
the path below that prefix.

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
| `solved` | nullable boolean | `null` = no riddle result (including consumed `NullEngine` tokens), `true` = correct answer, `false` = wrong answer. Set during verification. |

A migration is shipped:

```
bin/cake migrations migrate -p Captcha
```

Existing rows backfill to `null` (they predate the tracking). Solve-rate computations exclude `null` rows so historical data does not skew the ratio.

## Currently rate-limited clients

The dashboard and IP list read live counters from `Captcha.verifyRateLimit.cache`.
A small cache registry records the IP, counter key, threshold, and bucket expiry when a failure
is counted. It stores no raw session IDs. This includes passive-only clients and failed token
lookups that have no corresponding database row. Separate sessions below the threshold are
not combined into a false throttle report. Clearing a counter removes it from the displayed
throttles immediately.

`Unblock` deletes registered counters for the IP, including passive-only sessions. It also clears
keys derived from recent database rows for compatibility with counters created before this update.
The issued, solved, and failed leaderboards still describe database rows; honeypot attempts do
not become solved or failed riddles. The admin pages still require the plugin's database schema.

The registry retains at most 1,000 recently updated counters per cache configuration and prunes
expired buckets. It is a best-effort admin view: cache eviction, registry lock contention, or the
capacity limit can omit clients, and omitted passive-only keys cannot be discovered by `Unblock`.
Enforcement reads each counter directly and does not depend on this registry. Keep the behavior
and admin cache settings aligned. Use external monitoring if you need a complete abuse history.
