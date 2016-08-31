<?php

namespace App\Presenters;

class AdminPresenter extends SecuredPresenter {

	protected function startup() {
		parent::startup();

		if ($this->user->roles["Admin"] != "1") {
			$this->flashMessage("Pro přístup do této sekce nemáte dostatečné oprávnění.", "authentification");
			$this->redirect("Article:default");
		}
	}
}
