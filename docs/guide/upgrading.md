# Upgrading

Update the package with Composer and apply the plugin migrations when using stored challenges or admin pages:

```sh
composer update dereuromark/cakephp-captcha
bin/cake migrations migrate -p Captcha
```

Test both accepted and rejected submissions after upgrading. UUID, session, IP, timing, and single-use checks all affect active validation.

## Unreleased fixes

The current branch includes these fixes after the previous release:

- `control()` includes the hidden `captcha_uuid` input required by active validation. If you manually added that input to work around its absence, remove the duplicate.
- Automatic component setup accepts fully qualified table classes and safely handles controllers with no default table. Use `auto => false` for model-less forms.
- Model-less passive validation no longer initializes the captcha database table.
- `cleanupProbability => 0` disables automatic cleanup when issuing tokens.
- Admin host redirects stop later event listeners. Unexpected callback failures deny access while HTTP exceptions retain their behavior, and the request Authorization service is supported when its component is absent.

`input()` remains a deprecated alias of `control()`. Use `control()` or `render()` in new templates.

Subclasses overriding `CaptchaComponent::addValidation()` must declare its new `void` return type.
