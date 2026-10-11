# Overview

Captcha adds validation rules and form controls to CakePHP applications. Choose the protection needed by your form:

| Setup | Visitor interaction | State |
| --- | --- | --- |
| `Captcha.PassiveCaptcha` | Hidden honeypot | No database; no cache or session unless throttling is enabled |
| `Captcha.Captcha` with `MathEngine` | Math riddle | Database token, session, timing checks, verification cache |
| `Captcha.Captcha` with `NullEngine` | No riddle | Database token, session, timing checks, verification cache |

Honeypots and simple math catch basic automated submissions. They do not replace CSRF protection, authentication, or stronger controls needed for high-risk forms.

The [beginner guide](./getting-started) starts with a honeypot on an existing form. Continue with [active captchas](../reference/active) for a stored challenge. Both behaviors can be attached to the same table.

These docs describe the CakePHP 6 development branch. Check [upgrading](./upgrading) for changes that have not yet been released. Older CakePHP versions have a [version map](https://github.com/dereuromark/cakephp-captcha/wiki#cakephp-version-map).
