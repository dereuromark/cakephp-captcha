# Captcha Plugin Documentation

This plugin aims to ship with robust and most importantly "user-friendly" captchas.
There is nothing more annoying as captcha images you can't make out the content for 5+ trials.

It is also not supposed to replace the Security/Csrf components and bot-protection mechanisms.
More likely one would use them side by side.

Simple math captchas are also usually a bit more fun than trying to figure out some unreadable words behind colorful bars.
But since this plugin ships with a highly extensible interface solution, you can write and use your own captcha image solution.

## Active vs Passive

This plugin ships with two different types of captchas:

- [Active](Active.md): User input required
- [Passive](Passive.md): Honeypot trap and additional bot protection

They can also be combined for maximum captcha effectiveness.


## Basic Usage
Using the default MathEngine we can simply attach the behavior to the Table class.

Load the helper, e.g in your AppView:
```php
$this->loadHelper('Captcha.Captcha');
```

Add a captcha control (active + passive) in your form:
```php
echo $this->Captcha->render(['placeholder' => __('Please solve the riddle')]);
```

Add the behavior at runtime in your controller action:
```php
$this->Ads->addBehavior('Captcha.Captcha');
```
If you want to also use the passive one, also add:
```php
$this->Ads->addBehavior('Captcha.PassiveCaptcha');
```

Saving a new ad would now require a valid captcha solution.
```php
// This would come from the form POST
$postData = [
    'title' => 'Looking for a friend',
];

$ad = $this->Ads->newEntity($postData);
$success = $this->Ads->save($ad);
```

For detailed documentation see the above docs on active and passive ones.
