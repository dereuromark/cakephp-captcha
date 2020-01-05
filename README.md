# Captcha Plugin for CakePHP
[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-captcha.svg)](https://travis-ci.org/dereuromark/cakephp-captcha)
[![Coverage Status](https://codecov.io/gh/dereuromark/cakephp-captcha/branch/master/graph/badge.svg)](https://codecov.io/gh/dereuromark/cakephp-captcha)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-captcha/license)](https://packagist.org/packages/dereuromark/cakephp-captcha)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-captcha/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-captcha)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

Allows any type of image-based captchas for your forms.

## A CakePHP plugin to
- Easily integrate captchas in your CakePHP application.

This plugin requires **CakePHP 4.0+**. See [version map](https://github.com/dereuromark/cakephp-captcha/wiki#cakephp-version-map) for details.

### Whats in this plugin
- Simple match captchas that will already do the trick without annoying "I can't read those letters".
- Extendable interface approach to easily hook in your own engine.

### Whats the gotchas
- Dead simple, no fancy JS or alike.
- Cross-tab safe (not session based as in overwriting each other per request).
- Completely stand-alone plugin, no third party stuff involved/needed.

## Demo
See https://sandbox.dereuromark.de/sandbox/captchas

## Setup
```
composer require dereuromark/cakephp-captcha
```
and
```
bin/cake plugin load Captcha -b -r
```

You also need to import the SQL schema.
The quickest way is using Migrations plugin:
```
bin/cake migrations migrate -p Captcha
```

## Basic Usage
Using the default MathEngine we can simply attach the behavior to the Table class.

Load the helper, e.g in your AppView:
```php
$this->loadHelper('Captcha.Captcha');
```

Add a captcha control in your form:
```php
echo $this->Captcha->render(['placeholder' => __('Please solve the riddle')]);
```

Add the behavior at runtime in your controller action:
```php
$this->Ads->addBehavior('Captcha.Captcha');
```

Saving a new ad would now require a valid captcha solution.
```php
// This would come from the form POST
$postData = [
    'title' => 'Looking for a friend',
];
$ad = $this->Ads->newEntity($postData);
$success = $this->Users->save($user);
```

For detailed documentation see **[Docs](docs)**.

