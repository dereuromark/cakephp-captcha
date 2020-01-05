<?php

namespace Captcha\View;

use App\View\AppView;

/**
 * Renders the captcha image.
 */
class CaptchaView extends AppView {

	/**
	 * The name of the layout file to render the view inside of. The name specified
	 * is the filename of the layout in /app/Template/Layout without the .ctp
	 * extension.
	 *
	 * @var string
	 */
	public $layout = '';

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
