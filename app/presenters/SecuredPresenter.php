<?php

namespace App\Presenters;

use Nette\Http\UserStorage;

class SecuredPresenter extends BasePresenter {

	protected function startup() {
		parent::startup();

		if (!$this->user->isLoggedIn()) {
			if ($this->user->logoutReason === UserStorage::INACTIVITY) {
				$this->flashMessage("Byl(a) jste odhlášen(a) z důvodu nekativity. Přihlašte se prosím znovu.", "authentification");
			} else {
				$this->flashMessage("Pro přístup do této sekce musíte být přihlášen(a).", "authentification");
			}
			$this->redirect("Sign:in");
		}
	}

}
