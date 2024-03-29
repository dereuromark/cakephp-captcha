<?php

namespace Captcha\Model\Rule;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Log\Log;

class MaxRule {

	/**
	 * @param \Captcha\Model\Entity\Captcha $entity
	 * @param array<string, mixed> $options
	 *
	 * @return bool
	 */
	public function __invoke(EntityInterface $entity, array $options): bool {
		$limit = Configure::read('Captcha.maxPerUser') ?: 100;

		/** @var \Captcha\Model\Table\CaptchasTable $repository */
		$repository = $options['repository'];

		$success = $repository->getCount($entity->ip, $entity->session_id) < (int)$limit;
		if (!$success) {
			Log::write('info', "Too many captchas attempts for $entity->ip");
		}

		return $success;
	}

}
