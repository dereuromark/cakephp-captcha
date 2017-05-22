# Captcha Plugin Documentation

This plugin aims to ship with robust and most importantly "user friendly" catpchas.
There is nothing more annoying as captcha images you can't make out the content for 5+ trials.

It is also not supposed to replace the Security and Csrf components and bot-protection mechanisms.
More likely one would use them side by side.

Simple math captchas are also usually a bit more fun than trying to figure out some unreadable words behind colorful bars.
But since this plugin ships with a highly extensible interface solution, you can write and use your own captcha image solution.

## Forms with tables
You can add the behavior to your model inside the specific action: or you can simply
```php
$this->MyTable->addBehavior('Captcha.Captcha');
```

You can also use the component (and the optional actions array) to auto-add the behavior to your model: 
```php
// inside initialize() of controller
$this->loadComponent('Captcha.Captcha');
```
The component has the advantage that it also auto-loads the Captcha helper for your template forms.
Otherwise you need to manually load it.

If you only want to validate captchas for certain actions, the component can be skipped for the actions not in the "actions" array:
```php
$this->loadComponent('Captcha.Captcha', [
	'actions' => ['add', 'edit'],
]);
```


## Working with model-less forms
E.g. for a contact form, first add this in your controller's `initialize()`:
```php
$this->loadComponent('Captcha.Captcha');
```

Then inside your action, use `addValidation()` to inject the behavior's validation rules into the form validator:
```php
$contactForm = new ContactForm();

if ($this->request->is('post')) {
	$this->Captcha->addValidation($contactForm->validator());
	
	if ($contactForm->execute($this->request->data)) {
		// Send email and redirect
	}
	// Display validation errors
}
```

## Configuration

You can configure it globally using Configure class - and `app.php`:
```
'Captcha' => [
	'engine' => ...,
	'mathType => ...,
	'imageType => ...,
	'complexity' => ...,
	...
]
```

If you configure it locally, make sure you set it to the same for both Component and Behavior.

## Exchanging the MathEngine `mathType`

If the `SimpleMath` addition/substraction does not cut it for you, you can simply hook in your won class.
```php
namespace App\Engine\Math;

use Captcha\Engine\Math\MathInterface;

class ComplexMath implements MathInterface {

	/**
	 * @return string
	 */
	public function getExpression() {
		...
	}

	/**
	 * @return string
	 */
	public function getValue() {
		...
	}

}
```

## Use your own Captcha engine

You can also completely switch the engine by using the `engine` Configure key:
```
'engine' => 'App\Engine\WordEngine'
```

The engine could look like:
```php
namespace App\Engine;

use Captcha\Engine\EngineInterface;

class WordEngine implements EngineInterface {

	/**
	 * @return array
	 */
	public function generate() {
		...
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 * @return void
	 */
	public function buildValidator(Validator $validator) {
		...
	}

}
```

## Throttle and Garbage Collect
With such a DB driven tool you must clean out your table daily or even hourly based on the traffic.
Use the `maxTime` config to define the time span a captcha is valid, and when it can be removed as outdated.
The built in solution here is to auto-clean when creating images on a probability level (0...100). Default for `cleanupProbability` is `10` (percent).
You can also use a simple cron job that does it. Set `cleanupProbability` to `0` then.

The `minTime` is by default 2 seconds and make sure you cannot auto-post a form too fast.

One should also include a throttle limit, so you cannot fill up the DB.
The built in mechanism is a `maxPerUser` value (defaults to 1000) which prevents entering more than this amount per ip or session.
If a form gets built and sent too often, those captcha results will never validate then (as their result has not been persisted anymore due to this rate limit).

## Honeypot
The `dummyField` config sets an input field (by default `email_homepage` control) as hidden field, and will fail if filled out.
This can only happen by a bot, which usually fills out all fields it finds.
