<?php

namespace Captcha\Engine;

use Cake\Validation\Validator;
use Captcha\Engine\Math\SimpleMath;

class MathEngine implements EngineInterface {

	/**
	 * @var array
	 */
	protected $config;

	public function __construct(array $config) {
		$this->config = $config + ['type' => SimpleMath::class];
	}

    /**
     * @param array $config
     *
     * @return array
     */
    public function generate() {
		$class = $this->_getTypeClass();

		$expression = $class->getExpression();
		$value = $class->getValue();

		$image = $this->render($expression);

		return [
			'value' => $value,
			'image' => $image
		];
    }

    /**
     * @param Validator $validator
     *
     * @return void
     */
    public function buildValidator(Validator $validator) {
        $validator->add('captcha_result', [
            'valid' => [
                'rule' => 'validateCaptchaResult',
                'provider' => 'table',
                'message' => __('The solution is not correct'),
            ]
        ]);
    }

    /**
     * @return string Binary image data
     */
    protected function render($expression)
    {
        // TODO: Implement render() method.
    }

	/**
	 * @return \Captcha\Engine\Math\MathInterface
     */
	protected function _getTypeClass() {
		$config = $this->config;
		return new $config['type']($config);
	}

}
