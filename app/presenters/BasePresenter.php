<?php

namespace App\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Forms\Controls;

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
		$this->flashMessage("Při zpracování se vyskytla chyba. Odešlete prosím formulář znovu.", "reload");
	}

	/**
	 * Add classes for form to make it use bootstrap
	 * https://github.com/nette/forms/blob/a0bc775b96b30780270bdec06396ca985168f11a/examples/bootstrap3-rendering.php#L58
	 * @param  Form $form form for applying styles
	 */
	protected function makeBootstrapForm($form) {
		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = null;
		$renderer->wrappers['pair']['container'] = 'div class="row form-group"';
		$renderer->wrappers['pair']['.error'] = 'has-error';
		$renderer->wrappers['label']['container'] = 'div class="col-sm-2 control-label"';
		$renderer->wrappers['control']['container'] = 'div class="col-sm-9"';
		$renderer->wrappers['control']['description'] = 'span class="help-block"';
		$renderer->wrappers['control']['errorcontainer'] = 'span class="help-block"';
		$renderer->wrappers['control']['.text'] = 'text form-control';
		$renderer->wrappers['control']['.password'] = 'text form-control';
		$renderer->wrappers['control']['.submit'] = 'btn btn-primary';
		$form->getElementPrototype()->class('form-horizontal');

		foreach ($form->getControls() as $control) {
			if ($control instanceof Controls\Button) {
				$control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-default');
				$usedPrimary = TRUE;
			} elseif ($control instanceof Controls\TextBase || $control instanceof Controls\SelectBox || $control instanceof Controls\MultiSelectBox) {
				$control->getControlPrototype()->addClass('form-control');
			} elseif ($control instanceof Controls\Checkbox || $control instanceof Controls\CheckboxList || $control instanceof Controls\RadioList) {
				$control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
			}
		}
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
			->setAttribute("class", "btn-info")
			->onClick[] = [$_this, $cancelAction];

		$form->addSubmit("delete", "Smazat")
			->setAttribute("class", "btn-danger")
			->onClick[] = [$_this, $okAction];

		$this->addFormProtection($form);

		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = null;
		$renderer->wrappers['pair']['container'] = null;
		$renderer->wrappers['pair']['.error'] = 'has-error';
		$renderer->wrappers['label']['container'] = null;
		$renderer->wrappers['control']['container'] = '';
		$renderer->wrappers['control']['.submit'] = 'btn btn-lg';
		$form->getElementPrototype()->class('form-horizontal');
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
