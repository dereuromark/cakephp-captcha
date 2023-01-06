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
	protected array $helpers = ['Form', 'Html', 'Url'];

	/**
	 * @var int|null
	 */
	protected $_id;

	/**
	 * Options:
	 * - ext: jpg, png (defaults to none)
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'ext' => null,
	];

	/**
	 * @deprecated Use control()
	 *
	 * @param array<string, mixed> $options
	 *
	 * @return string
	 */
	public function input(array $options = []) {
		return $this->control($options);
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return string
	 */
	public function control(array $options = []) {
		$options += [
			'label' => ['escape' => false, 'text' => $this->image()],
			'escapeLabel' => false,
			'autocomplete' => 'off',
			'value' => '',
		];

		return $this->Form->control('captcha_result', $options);
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return string
	 */
	public function render(array $options = []) {
		$id = $this->_getId();

		$html = $this->control($options);
		$html .= $this->Form->control('captcha_id', ['type' => 'hidden', 'value' => $id]);
		$html .= $this->passive();

		return $html;
	}

	/**
	 * Add a honey pot trap field. Requires corresponding validation to be activated.
	 *
	 * If you pass null, it will use the configured default field name.
	 *
	 * @param array|string|null $field
	 *
	 * @return string
	 */
	public function passive($field = null) {
		if (!$field) {
			$field = $this->getConfig('dummyField') ?: 'email_homepage';
		}
		$dummyFields = (array)$field;

		$html = [];
		foreach ($dummyFields as $dummyField) {
			$html[] = '<div style="display: none">' . $this->Form->text($dummyField, ['default' => '']) . '</div>';
		}

		return implode(PHP_EOL, $html);
	}

	/**
	 * @param array<string, mixed> $options
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
		$ext = $this->getConfig('ext');

		return $this->Url->build(['prefix' => false, 'plugin' => 'Captcha', 'controller' => 'Captcha', 'action' => 'display', $id, '_ext' => $ext], ['fullBase' => true]);
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
		$table = TableRegistry::getTableLocator()->get('Captcha.Captchas');

		return $table;
	}

}
