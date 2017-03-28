# Captcha Plugin Documentation

This plugin aims to ship with robust and most importantly "user friendly" catpchas.
There is nothing more annoying as captcha images you can't make out the content for 5+ trials.

It is also not supposed to replace the Security and Csrf components and bot-protection mechanisms.
More likely one would use them side by side.

Simple math captchas are also usually a bit more fun than trying to figure out some unreadable words behind colorful bars.
But since this plugin ships with a highly extensible interface solution, you can write and use your own captcha image solution.

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
