# Upgrading

Update the package with Composer and apply the plugin migrations when using stored challenges or admin pages:

```sh
composer update dereuromark/cakephp-captcha
bin/cake migrations migrate -p Captcha
```

Test both accepted and rejected submissions after upgrading. UUID, session, IP, timing, and single-use checks all affect active validation.

## CakePHP 6 development branch

Use PHP 8.5 and the development dependencies described in [Installation](installation.md).
Host controller `beforeFilter()` methods return `void`; set redirect responses on the
event with `$event->setResult($this->redirect('/login'))`.

CakePHP 6 no longer forwards behavior methods through tables. Access captcha
validation methods through `$table->getBehavior('Captcha')`. The plugin registers
that behavior as the `captcha` validation provider. Custom engines using
`validateCaptchaResult` should use `'provider' => 'captcha'` in their rules.

Custom helpers and behaviors must use the framework's renamed `defaultConfig`,
`config`, `View`, and `registry` properties where applicable. Entity field access
is configured with `patchable`, replacing `_accessible`.

## Earlier fixes

The current branch includes these fixes after the previous release:

- `control()` includes the hidden `captcha_uuid` input required by active validation. If you manually added that input to work around its absence, remove the duplicate.
- Automatic component setup accepts fully qualified table classes and safely handles controllers with no default table. Use `auto => false` for model-less forms.
- Model-less passive validation no longer initializes the captcha database table.
- `cleanupProbability => 0` disables automatic cleanup when issuing tokens.
- Admin host redirects stop later event listeners. Unexpected callback failures deny access while HTTP exceptions retain their behavior, and the request Authorization service is supported when its component is absent.

`input()` remains a deprecated alias of `control()`. Use `control()` or `render()` in new templates.

Subclasses overriding `CaptchaComponent::addValidation()` must declare its new `void` return type.
