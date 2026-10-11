# Installation

This development branch requires CakePHP 6 and PHP 8.5. Allow development
dependencies while preferring stable versions of other packages.

```sh
composer config minimum-stability dev
composer config prefer-stable true
composer require cakephp/cakephp:^6.0@dev dereuromark/cakephp-captcha:dev-cake6
bin/cake plugin load Captcha
```

If you load plugins in `src/Application.php`, use `$this->addPlugin('Captcha')` in `bootstrap()` after the parent call instead of the load command.

For a honeypot, continue with [Getting started](./getting-started). No plugin database schema is needed for that setup.

## Stored challenges and admin pages

Active captchas, `NullEngine`, and the admin interface require the plugin schema on your application's default connection. If Migrations is not installed:

```sh
composer require cakephp/migrations:dev-fix/6.x-cakephp-compatibility
bin/cake plugin load Migrations
```

The migration dependency currently uses the upstream CakePHP 6 compatibility
branch. Then apply all plugin migrations:

```sh
bin/cake migrations migrate -p Captcha
```

Run the migrations during deployment and after plugin upgrades. They include UUID tokens and result tracking used by the current code.

The default `MathEngine` also needs PHP GD with FreeType support and the bundled font resources. Install the GD extension for the PHP version used by your web server. Honeypots and `NullEngine` do not need GD.

See [Active captchas](../reference/active) for form setup and [Admin interface](../reference/admin) for access configuration.
