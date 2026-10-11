<?php

namespace TestApp\Model\Behavior;

use Captcha\Model\Behavior\CaptchaBehavior;

class LegacyRateLimitBehavior extends CaptchaBehavior {

	public bool $enabled = true;

	protected function _buildRateLimitKey(): string {
		return 'tenant_' . parent::_buildRateLimitKey();
	}

	protected function _isVerifyRateLimitEnabled(): bool {
		return $this->enabled && parent::_isVerifyRateLimitEnabled();
	}

	public function limited(): bool {
		return $this->_isRateLimited();
	}

	public function recordFailure(): void {
		$this->_incrementFailedAttemptCounter();
	}

	public function clearFailures(): void {
		$this->_clearFailedAttemptCounter();
	}

}
