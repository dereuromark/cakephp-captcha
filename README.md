# Captcha Plugin for CakePHP
[![Build Status](https://api.travis-ci.com/dereuromark/cakephp-captcha.svg)](https://travis-ci.com/dereuromark/cakephp-captcha)
[![Coverage Status](https://codecov.io/gh/dereuromark/cakephp-captcha/branch/master/graph/badge.svg)](https://codecov.io/gh/dereuromark/cakephp-captcha)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-captcha/license)](https://packagist.org/packages/dereuromark/cakephp-captcha)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-captcha/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-captcha)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

Allows any type of image-based captchas for your forms.

## A CakePHP plugin to
- Easily integrate captchas in your CakePHP application.

This plugin requires **CakePHP 4.0+**. See [version map](https://github.com/dereuromark/cakephp-captcha/wiki#cakephp-version-map) for details.

### What's in this plugin
- Simple match captchas that will already do the trick without annoying "I can't read those letters".
- Passive captcha option for basic protection without requiring user input ("honeypot trap").
- Extendable interface approach to easily hook in your own engine.

### What are the gotchas
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

For active captchas you also need to import the SQL schema.
The quickest way is using Migrations plugin:
```
bin/cake migrations migrate -p Captcha
```

For the match captcha, make sure you got the gd lib installed:
- `sudo apt-get install php{version}-gd`

## Usage
See [Docs](docs/).

