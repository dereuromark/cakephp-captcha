<?php

namespace Captcha\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
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
			'uuid' => Text::uuid(),
			'result' => 3,
			'ip' => '127.0.0.1',
			'session_id' => $this->request->getSession()->id() ?: 'test',
			'created' => new DateTime('- 1 hour'),
			'modified' => new DateTime('- 1 hour'),
		]);
		$result = $this->Captchas->save($captcha);
		$this->assertTrue((bool)$result);
		$uuid = $captcha->uuid;

		$data = [
			'comment' => 'Foo',
		];
		$comment = $this->Comments->newEntity($data);
		$res = $this->Comments->save($comment);
		$this->assertFalse((bool)$res);

		$data['captcha_uuid'] = $uuid;
		$data['captcha_result'] = 3;
		$data['email_homepage'] = '';

		$comment = $this->Comments->newEntity($data);
		$res = $this->Comments->save($comment);
		$this->assertTrue((bool)$res);

		$captcha = $this->Captchas->get($captcha->id);
		$this->assertNotEmpty($captcha->used);
	}

	/**
	 * @return void
	 */
	public function testCaptchaCannotBeReplayedAfterSuccess() {
		$captcha = $this->Captchas->newEntity([
			'uuid' => Text::uuid(),
			'result' => 7,
			'ip' => '127.0.0.1',
			'session_id' => $this->request->getSession()->id() ?: 'test',
			'created' => new DateTime('- 1 hour'),
			'modified' => new DateTime('- 1 hour'),
		]);
		$this->assertTrue((bool)$this->Captchas->save($captcha));

		$data = [
			'comment' => 'Foo',
			'captcha_uuid' => $captcha->uuid,
			'captcha_result' => 7,
			'email_homepage' => '',
		];

		$comment = $this->Comments->newEntity($data);
		$this->assertTrue((bool)$this->Comments->save($comment));

		$comment = $this->Comments->newEntity($data);
		$this->assertFalse((bool)$this->Comments->save($comment));
	}

	/**
	 * @return void
	 */
	public function testCaptchaIsConsumedAfterFailedAttempt() {
		$captcha = $this->Captchas->newEntity([
			'uuid' => Text::uuid(),
			'result' => 9,
			'ip' => '127.0.0.1',
			'session_id' => $this->request->getSession()->id() ?: 'test',
			'created' => new DateTime('- 1 hour'),
			'modified' => new DateTime('- 1 hour'),
		]);
		$this->assertTrue((bool)$this->Captchas->save($captcha));

		$data = [
			'comment' => 'Foo',
			'captcha_uuid' => $captcha->uuid,
			'captcha_result' => 8,
			'email_homepage' => '',
		];

		$comment = $this->Comments->newEntity($data);
		$this->assertFalse((bool)$this->Comments->save($comment));

		$data['captcha_result'] = 9;
		$comment = $this->Comments->newEntity($data);
		$this->assertFalse((bool)$this->Comments->save($comment));

		$captcha = $this->Captchas->get($captcha->id);
		$this->assertNotEmpty($captcha->used);
	}

	/**
	 * @return void
	 */
	public function testCaptchaValidationRateLimit() {
		Configure::write('Captcha.verifyRateLimit', [
			'enabled' => true,
			'maxFailures' => 2,
			'window' => 600,
			'scope' => 'ip_session',
			'cache' => 'default',
		]);
		$this->Comments->removeBehavior('Captcha');
		$this->Comments->addBehavior('Captcha.Captcha');

		$captchaOne = $this->Captchas->newEntity([
			'uuid' => Text::uuid(),
			'result' => 11,
			'ip' => '127.0.0.1',
			'session_id' => $this->request->getSession()->id() ?: 'test',
			'created' => new DateTime('- 1 hour'),
			'modified' => new DateTime('- 1 hour'),
		]);
		$captchaTwo = $this->Captchas->newEntity([
			'uuid' => Text::uuid(),
			'result' => 13,
			'ip' => '127.0.0.1',
			'session_id' => $this->request->getSession()->id() ?: 'test',
			'created' => new DateTime('- 1 hour'),
			'modified' => new DateTime('- 1 hour'),
		]);
		$captchaThree = $this->Captchas->newEntity([
			'uuid' => Text::uuid(),
			'result' => 17,
			'ip' => '127.0.0.1',
			'session_id' => $this->request->getSession()->id() ?: 'test',
			'created' => new DateTime('- 1 hour'),
			'modified' => new DateTime('- 1 hour'),
		]);
		$this->assertTrue((bool)$this->Captchas->save($captchaOne));
		$this->assertTrue((bool)$this->Captchas->save($captchaTwo));
		$this->assertTrue((bool)$this->Captchas->save($captchaThree));

		$data = [
			'comment' => 'Foo',
			'captcha_result' => 99,
			'email_homepage' => '',
		];

		$comment = $this->Comments->newEntity($data + ['captcha_uuid' => $captchaOne->uuid]);
		$this->assertFalse((bool)$this->Comments->save($comment));

		$comment = $this->Comments->newEntity($data + ['captcha_uuid' => $captchaTwo->uuid]);
		$this->assertFalse((bool)$this->Comments->save($comment));

		$comment = $this->Comments->newEntity($data + ['captcha_uuid' => $captchaThree->uuid]);
		$this->assertFalse((bool)$this->Comments->save($comment));
		$this->assertSame(
			'Too many failed attempts. Please retry later',
			$comment->getError('captcha_result')['verifyRateLimit'] ?? null,
		);
	}

}
