<?php

namespace Captcha\View;

use App\View\AppView;

/**
 * Renders the captcha image.
 */
class CaptchaView extends AppView {

	/**
	 * @var string
	 */
	protected $layout = '';

	/**
	 * Initialization hook method.
	 *
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->disableAutoLayout();

		$this->response = $this->response->withType('png')
			->withHeader('Content-Transfer-Encoding', 'binary');
	}

}
