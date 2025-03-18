<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Core\Controller\ClientFlowLoginV2Controller;
use OC\Core\Controller\TwoFactorChallengeController;
use OCP\AppFramework\Middleware;
use OCP\ISession;
use OCP\IUserSession;

// Will close the session if the user session is ephemeral.
// Happens when the user logs in via the login flow v2.
class FlowV2EphemeralSessionsMiddleware extends Middleware {
	private ISession $session;
	private IUserSession $userSession;
	private ControllerMethodReflector $reflector;

	public function __construct(
		ISession $session,
		IUserSession $userSession,
		ControllerMethodReflector $reflector
	) {
		$this->session = $session;
		$this->userSession = $userSession;
		$this->reflector = $reflector;
	}

	public function beforeController($controller, $methodName) {
		if (!$this->session->get(ClientFlowLoginV2Controller::EPHEMERAL_NAME)) {
			return;
		}

		if (
			$controller instanceof ClientFlowLoginV2Controller &&
			($methodName === 'grantPage' || $methodName === 'generateAppPassword')
		) {
			return;
		}

		if ($controller instanceof TwoFactorChallengeController) {
			return;
		}

		if ($this->reflector->hasAnnotation('PublicPage')) {
			return;
		}

		$this->userSession->logout();
		$this->session->close();
	}
}
