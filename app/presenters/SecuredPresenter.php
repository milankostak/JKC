<?php

namespace App\Presenters;

use Nette\Http\UserStorage;

class SecuredPresenter extends BasePresenter {

	protected function startup() {
		parent::startup();

		if (!$this->user->isLoggedIn()) {
			if ($this->user->logoutReason === UserStorage::INACTIVITY) {
				$this->flashMessages->flashMessageAuthentification("Byl(a) jste odhlášen(a) z důvodu neaktivity. Přihlašte se prosím znovu.");
			} else {
				$this->flashMessages->flashMessageAuthentification("Pro přístup do této sekce musíte být přihlášen(a).");
			}
			$this->redirect("Sign:in");
		}
	}

}
