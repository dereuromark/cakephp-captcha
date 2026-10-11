# Configuration

Put shared engine and helper settings under the `Captcha` key in `config/app.php` or `app_local.php`. Load runtime callbacks with `Configure::write()` in bootstrap code. See the [example configuration](https://github.com/dereuromark/cakephp-captcha/blob/master/config/app.example.php).

| Setting | Default | Meaning |
| --- | --- | --- |
| `engine` | `MathEngine::class` | Engine used by stored challenges |
| `minTime` | `2` | Minimum age in seconds for active verification |
| `maxTime` | `86400` | Maximum token age and cleanup retention in seconds |
| `mathType` | `SimpleMath::class` | Math expression generator |
| `imageFormat` | `MathEngine::FORMAT_PNG` | PNG or JPEG image bytes |
| `size` | `14` | Math image rendering size |
| `ext` | `null` | Image URL extension; use `jpg` for JPEG |
| `dummyField` | `email_homepage` | Honeypot name or array of names |
| `passiveClass` | `null` | CSS class replacing the honeypot's inline style |
| `log` | `null` | Honeypot hit logging; defaults to debug mode |
| `cleanupProbability` | `10` | Percentage chance of deleting used or expired tokens during issuance |
| `maxPerUser` | `100` | Unused tokens allowed for either the IP or session within the issuance window |
| `deadlockMinutes` | `60` | Issuance window in minutes |

A `maxPerUser` value of zero falls back to 100. It does not disable the issuance limit. `cleanupProbability => 0` disables automatic cleanup on the current branch.

## Scope of settings

The active and passive behaviors merge global settings with per-behavior options. Partial `verifyRateLimit` overrides inherit the other global limiter options. The helper and public image preparation also read global settings, so keep engine choices consistent by configuring them globally.

`CaptchaComponent` accepts `auto` (default `true`) and an `actions` array (default empty, meaning all actions). The action list restricts automatic behavior attachment. Helper loading still runs before rendering. `auto => false` disables automatic table attachment while retaining helper loading and `addValidation()` for model-less forms.

## Verification limits

Active token validation enables the verification limiter by default; passive validation leaves it disabled. Both default to five failures per 600-second fixed bucket, `ip_session` scope, and the `default` cache. Configure a shared cache with atomic operations for concurrent workers. See [active cache requirements](./active#verification-cache) and [passive throttling](./passive#optional-verification-throttling).

`engineConfig` is used only by the admin preview. Production engine options belong at the top level of `Captcha`, alongside `engine`.

Admin routes, layout, and access settings are covered in [Admin interface](./admin). The plugin has no standalone admin mode: the host controller lifecycle still runs.
