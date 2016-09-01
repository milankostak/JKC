<?php

namespace App\Presenters\Utils;

class FormUtils implements IFormUtils {

	const RECOVER_DATA_SESSION = "recover_data";

	private $presenter;

	function __construct(\Nette\Application\UI\Presenter $presenter) {
		$this->presenter = $presenter;
	}

	/**
	 * Create and set uid token
	 * @param  Form   $form
	 * @param  string $tokenName
	 */
	public function manageUidToken($form, $tokenName) {
		$uid = md5(uniqid(rand(), true));
		if ($this->presenter->getParameter("do") == "") {
			$this->presenter->getSession($tokenName)[$uid] = $uid;
		}
		$form->addHidden("uid")->setValue($uid);
	}

	/**
	 * Recovery of form data after problem with the form
	 * @param  Form $form form object
	 */
	public function recoverData($form) {
		if ($this->presenter->getSession(self::RECOVER_DATA_SESSION)) {
			if ($this->presenter->getSession(self::RECOVER_DATA_SESSION)->data) {
				$form->setDefaults($this->presenter->getSession(self::RECOVER_DATA_SESSION)->data);
				unset($this->presenter->getSession(self::RECOVER_DATA_SESSION)->data);
			}
		}
	}

	/**
	 * Save form data into session for later recovery
	 * @param  array $values values to save
	 */
	public function recoverInputs($values) {
		$data = [];
		foreach ($values as $key => $value) {
			$data[$key] = $value;
		}
		$this->presenter->getSession(self::RECOVER_DATA_SESSION)->data = $data;
		$this->presenter->redirect("this");
	}

	/**
	 * Add classes for form to make it use bootstrap
	 * https://github.com/nette/forms/blob/a0bc775b96b30780270bdec06396ca985168f11a/examples/bootstrap3-rendering.php#L58
	 * @param  Form $form form for applying styles
	 */
	public function makeBootstrapForm($form) {
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
	public function createOkCancelForm($form, $_this, $cancelAction, $okAction) {
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
	public function addFormProtection($form) {
		$form->addProtection("Doba platnosti tohoto formuláře vypršela. Odešlete jej prosím znovu. Je také možné, že ve svém prohlížeči máte vypnuté cookies.");
	}

}