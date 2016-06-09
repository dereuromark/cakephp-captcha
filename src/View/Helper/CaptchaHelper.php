<?php
namespace Captcha\View\Helper;

use Cake\Core\ConventionsTrait;
use Cake\ORM\TableRegistry;
use Cake\View\Helper;

/**
 * @property \Cake\View\Helper\FormHelper $Form
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class CaptchaHelper extends Helper {
	use ConventionsTrait;

	public $helpers = ['Form', 'Html', 'Url'];

	/**
	 * Default configuration.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [];

	protected $_id;

	/**
	 * Difficulty level
	 *
	 * @var int
	 */
	public $level;

	/**
	 * Font size.
	 *
	 * @var int
	 */
	public $size = 14;

	/**
	 * Allow decimal
	 *
	 * @var bool
	 */
	public $allowDecimal = false;

	public function input(array $options = []) {
		$options += [
			'label' => ['escape' => false, 'text' => $this->image()],
			'escapeLabel' => false,
		];

		return $this->Form->input('captcha_result', $options);
	}

	/**
	 * @param array $options
	 *
	 * @return string
	 */
	public function render(array $options = []) {
		$id = $this->_getId();

		$x = $this->input($options);
		$x .= $this->Form->input('captcha_id', ['type' => 'hidden', 'value' => $id]);

		$dummyField = $this->config('dummyField') ?: 'email_homepage';
		$x .= '<div style="display: none">' . $this->Form->input($dummyField, ['value' => '']) . '</div>';
		return $x;
	}

	/**
	 * @param array $options
	 *
	 * @return string HTML
	 */
	public function image(array $options = []) {
		return $this->Html->image($this->imageUrl(), $options = []);
	}

	/**
	 * @return string
	 */
	public function imageUrl() {
		$id = $this->_getId();
		return $this->Url->build(['prefix' => false, 'plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', $id], true);
	}

	/**
	 * @return int
	 */
	protected function _getId() {
		if ($this->_id) {
			return $this->_id;
		}
		$CaptchasTable = $this->_getTable();
		if (!$this->request->session()->started()) {
			$this->request->session()->start();
		}
		$this->_id = $CaptchasTable->touch($this->request->session()->id(), $this->request->clientIp());
		return $this->_id;
	}

	/**
	 * @return \Captcha\Model\Table\CaptchasTable
	 */
	protected function _getTable() {
		return TableRegistry::get('Captcha.Captchas');
	}

}
