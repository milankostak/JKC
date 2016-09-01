<?php

namespace App\Presenters\Utils;

class FlashMessages implements IFlashMessages {

	private $presenter;

	function __construct(\Nette\Application\UI\Presenter $presenter) {
		$this->presenter = $presenter;
	}

	/**
	 * Show green success flash message
	 * @param  string $message
	 */
	public function flashMessageSuccess($message) {
		$this->presenter->flashMessage($message, "success");
	}

	/**
	 * Show red error flash message
	 * @param  string $message
	 */
	public function flashMessageError($message) {
		$this->presenter->flashMessage($message, "error");
	}

	/**
	 * Show red authentification flash message
	 * @param  string $message
	 */
	public function flashMessageAuthentification($message) {
		$this->presenter->flashMessage($message, "authentification");
	}

	/**
	 * Show saving error flash message
	 */
	public function savingErrorFlashMessage() {
		$this->presenter->flashMessage("Při zpracování se vyskytla chyba. Odešlete prosím formulář znovu.", "reload");
	}
}