# Installation

Composer checks supported PHP and CakePHP versions from the package requirements.

```sh
composer require dereuromark/cakephp-captcha
bin/cake plugin load Captcha
```

If you load plugins in `src/Application.php`, use `$this->addPlugin('Captcha')` in `bootstrap()` after the parent call instead of the load command.

For a honeypot, continue with [Getting started](./getting-started). No plugin database schema is needed for that setup.

## Stored challenges and admin pages

Active captchas, `NullEngine`, and the admin interface require the plugin schema on your application's default connection. If Migrations is not installed:

```sh
composer require cakephp/migrations
bin/cake plugin load Migrations
```

Then apply all plugin migrations:

```sh
bin/cake migrations migrate -p Captcha
```

Run the migrations during deployment and after plugin upgrades. They include UUID tokens and result tracking used by the current code.

The default `MathEngine` also needs PHP GD with FreeType support and the bundled font resources. Install the GD extension for the PHP version used by your web server. Honeypots and `NullEngine` do not need GD.

See [Active captchas](../reference/active) for form setup and [Admin interface](../reference/admin) for access configuration.
