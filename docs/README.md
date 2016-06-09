# Captcha Plugin Documentation

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
