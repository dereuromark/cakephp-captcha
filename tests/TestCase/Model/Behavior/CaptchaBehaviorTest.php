<?php

namespace Captcha\Test\Model\Behavior;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use DateTime;

class CaptchaBehaviorTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.Captcha.Captchas',
		'plugin.Captcha.Comments',
	];

	/**
	 * @var \Cake\ORM\Table;
	 */
	protected $Captchas;

	/**
	 * @var \Cake\ORM\Table;
	 */
	protected $Comments;

	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected $request;

	/**
	 * @var \Cake\Http\Session
	 */
	protected $session;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		Configure::write('Captcha', [
			'debug' => false,
		]);

		$this->request = new ServerRequest();
		$this->request = $this->request->withEnv('REMOTE_ADDR', '127.0.0.1');
		Router::setRequest($this->request);

		$this->Captchas = $this->getTableLocator()->get('Captcha.Captchas');

		$this->Comments = $this->getTableLocator()->get('Captcha.Comments');
		$this->Comments->addBehavior('Captcha.Captcha');
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset($this->Comments, $this->Captchas);
		TableRegistry::getTableLocator()->clear();
	}

	/**
	 * @return void
	 */
	public function testSave() {
		$captcha = $this->Captchas->newEntity([
			'result' => 3,
			'ip' => '127.0.0.1',
			'session_id' => $this->request->getSession()->id() ?: 'test',
			'created' => new DateTime('- 1 hour'),
			'modified' => new DateTime('- 1 hour'),
		]);
		$result = $this->Captchas->save($captcha);
		$this->assertTrue((bool)$result);
		$id = $captcha->id;

		$data = [
			'comment' => 'Foo',
		];
		$comment = $this->Comments->newEntity($data);
		$res = $this->Comments->save($comment);
		$this->assertFalse((bool)$res);

		$data['captcha_id'] = $id;
		$data['captcha_result'] = 2;
		$data['email_homepage'] = '';

		$comment = $this->Comments->newEntity($data);
		$res = $this->Comments->save($comment);
		$this->assertFalse((bool)$res);

		$data['captcha_result'] = 3;

		$comment = $this->Comments->newEntity($data);
		$res = $this->Comments->save($comment);
		$this->assertTrue((bool)$res);

		$captcha = $this->Captchas->get($id);
		$this->assertNotEmpty($captcha->used);
	}

}
