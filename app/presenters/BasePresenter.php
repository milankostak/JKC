<?php

namespace App\Presenters;

use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter {

	const RECOVER_DATA_SESSION = "recover_data";

	protected function manageUidToken($form, $tokenName) {
		$uid = md5(uniqid(rand(), true));
		if ($this->getParameter("do") == "") {
			$this->getSession($tokenName)[$uid] = $uid;
		}
		$form->addHidden("uid")->setValue($uid);
	}

	/**
	 * Recovery of form data after problem with the form
	 * @param  Form $form form object
	 */
	protected function recoverData($form) {
		if ($this->getSession(self::RECOVER_DATA_SESSION)) {
			if ($this->getSession(self::RECOVER_DATA_SESSION)->data) {
				$form->setDefaults($this->getSession(self::RECOVER_DATA_SESSION)->data);
				unset($this->getSession(self::RECOVER_DATA_SESSION)->data);
			}
		}
	}

	/**
	 * Save form data into session for later recovery
	 * @param  array $values values to save
	 */
	protected function recoverInputs($values) {
		$data = [];
		foreach ($values as $key => $value) {
			$data[$key] = $value;
		}
		$this->getSession(self::RECOVER_DATA_SESSION)->data = $data;
		$this->redirect("this");
	}

	/**
	 * Show saving error flash message
	 */
	protected function savingErrorFlashMessage() {
		$this->flashMessage("Při zpracování se vyskytla chyba. Zopakujte prosím akci.", "reload");
		//Při ukládání   se vyskytla chyba. Zopakujte prosím akci.
		//Při zpracování se vyskytla chyba. Odešlete  prosím formulář znovu.
		//Při mazání     se vyskytla chyba. Zopakujte prosím akci.
		//Při mazání     se vyskytla chyba. Potvrďte  prosím formulář znovu.
	}

	/**
	 * Add protection to a form
	 * @param Form $form
	 */
	protected function addFormProtection($form) {
		$form->addProtection("Doba platnosti tohoto formuláře vypršela. Odešlete jej prosím znovu. Je také možné, že ve svém prohlížeči máte vypnuté cookies.");
	}

	/**
	 * Verify password
	 * @param  string $pass plain string to verify
	 * @param  string $hash hashed password
	 * @return boolen       true if passwords match, false otherwise
	 */
	protected function passwordVerify($pass, $hash) {
		require "/../model/password.php";
		return password_verify($pass, $hash);
	}

	/**
	 * Add cancel ad ok buttons into form
	 * @param  Form $form           form object
	 * @param  Presenter $_this     presenter reference
	 * @param  string $cancelAction name of cancel action
	 * @param  string $okAction     name of pass action
	 */
	protected function createOkCancelForm($form, $_this, $cancelAction, $okAction) {
		$form->addSubmit("cancel", "Zpět")
			->onClick[] = [$_this, $cancelAction];

		$form->addSubmit("save", "OK")
			->onClick[] = [$_this, $okAction];

		$this->addFormProtection($form);
	}

	/**
	 * Initialize custom latte filters
	 */
	protected function beforeRender() {
		/** Return czech month name according to its number */
		$this->template->addFilter("numericToStringMonth", function ($numericMonth) {
			switch ($numericMonth) {
				case 1: return "Leden";
				case 2: return "Únor";
				case 3: return "Březen";
				case 4: return "Duben";
				case 5: return "Květen";
				case 6: return "Červen";
				case 7: return "Červenec";
				case 8: return "Spren";
				case 9: return "Září";
				case 10: return "Říjen";
				case 11: return "Listopad";
				case 12: return "Prosinec";
			}
		});
	}
}
