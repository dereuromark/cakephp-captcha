<?php
namespace Captcha\View\Helper;

use Cake\ORM\TableRegistry;
use Cake\View\Helper;

/**
 * @property \Cake\View\Helper\FormHelper $Form
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class CaptchaHelper extends Helper {

	/**
	 * @var array
	 */
	public $helpers = ['Form', 'Html', 'Url'];

	/**
	 * Default configuration.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [];

	/**
	 * @var int|null
	 */
	protected $_id;

	/**
	 * @param array $options
	 *
	 * @return string
	 */
	public function input(array $options = []) {
		$options += [
			'label' => ['escape' => false, 'text' => $this->image()],
			'escapeLabel' => false,
			'autocomplete' => 'off',
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

		$html = $this->input($options);
		$html .= $this->Form->control('captcha_id', ['type' => 'hidden', 'value' => $id]);

		$dummyField = $this->getConfig('dummyField') ?: 'email_homepage';
		$html .= '<div style="display: none">' . $this->Form->control($dummyField, ['value' => '']) . '</div>';
		return $html;
	}

	/**
	 * @param array $options
	 *
	 * @return string HTML
	 */
	public function image(array $options = []) {
		return $this->Html->image($this->imageUrl(), $options);
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
		if (!$this->_View->getRequest()->getSession()->started()) {
			$this->_View->getRequest()->getSession()->start();
		}
		$id = $this->_View->getRequest()->getSession()->id();
		if (!$id && PHP_SAPI === 'cli') {
			$id = 'test';
		}
		$this->_id = $CaptchasTable->touch($id, $this->_View->getRequest()->clientIp());

		return $this->_id;
	}

	/**
	 * @return \Captcha\Model\Table\CaptchasTable
	 */
	protected function _getTable() {
		/** @var \Captcha\Model\Table\CaptchasTable $table */
		$table = TableRegistry::get('Captcha.Captchas');

		return $table;
	}

}
