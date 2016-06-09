<?php

namespace Captcha\View;

use App\View\AppView;

/**
 * A view class that is used for AJAX responses.
 * Currently only switches the default layout and sets the response type - which just maps to
 * text/html by default.
 */
class CaptchaView extends AppView {

	/**
	 * The name of the layout file to render the view inside of. The name specified
	 * is the filename of the layout in /app/Template/Layout without the .ctp
	 * extension.
	 *
	 * @var bool
	 */
	public $layout = false;

	/**
	 * Initialization hook method.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();

		$this->response->type('png');
		$this->response->header(['Content-Transfer-Encoding' => 'binary']);
	}

}
