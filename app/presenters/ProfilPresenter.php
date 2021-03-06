<?php

namespace App\Presenters;

use App\Model\Editor, App\Model\Blog;
use Nette\Application\UI\Form;

class ProfilPresenter extends SecuredPresenter {

	private $editors, $blog;

	private $editPassTokenName = "pass_editing";
	private $editNameTokenName = "name_editing";

	private $badNameError = "Uživatel s tímto loginem již existuje.";

	public function inject(Editor $editors, Blog $blog) {
		$this->editors = $editors;
		$this->blog = $blog;
	}

	protected function startup() {
		parent::startup();
		$this->blog = $this->blog->getBlogInfo();
		$this->template->big_title = $this->blog->name;
		$this->template->small_title = $this->blog->sub_name;
	}

	/**
	 * Redirect default page to Profil:name page
	 */
	public function actionDefault() {
		$this->redirect("name");
	}

	/**
	 * Page for editing name and login
	 */
	public function renderName() { }

	/**
	 * Form for editing name and login
	 * @return Nette\Application\UI\Form
	 */
	public function createComponentEditNameForm() {
		$form = new Form;

		$editor = $this->editors->findById($this->getUser()->id);

		$form->addText("name", "Jméno", 20)
			->setRequired("Vložte prosím jméno.")
			->addRule(Form::MAX_LENGTH, "Jméno je příliš dlouhé. Maximální délka je %d znaků.", 20)
			->setValue($editor->name);

		$form->addText("login", "Login", 20)
			->setRequired("Vložte prosím login.")
			->addRule(Form::MAX_LENGTH, "Login je příliš dlouhý. Maximální délka je %d znaků.", 20)
			->setValue($editor->login);

		$this->formUtils->recoverData($form);
		$this->formUtils->manageUidToken($form, $this->editNameTokenName);

		$form->addSubmit("save", "Uložit");
		$form->onSuccess[] = [$this, "saveName"];

		$this->formUtils->addFormProtection($form);
		$this->formUtils->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process saving name and login
	 * @param  Form  $form
	 * @param  array $values array of values from the form
	 */
	public function saveName(Form $form, $values) {
		$id = $this->getUser()->id;
		$uid = $values->uid;
		$t_name = $this->editNameTokenName;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			// check for duplicates
			if ($this->editors->checkForDuplicatesWithId($values->login, $id) == 0) {
				$this->editors->updateNameLogin($values, $id);
				$this->flashMessages->flashMessageSuccess("Údaje byly úspěšně změněny.");
				$this->redirect("this");
			} else {
				$this->flashMessages->flashMessageError($this->badNameError);
				$this->formUtils->recoverInputs($values);
			}
		// problem with session
		} else {
			$editor = $this->editors->findById($id);
			// action was performed, session is gone, but the data fits
			if ($values->name == $editor->name && $values->login == $editor->login) {
				$this->flashMessages->flashMessageSuccess("Údaje byly úspěšně změněny.");
				$this->redirect("this");
			// action was performed, session is gone and something is wrong
			} else {
				$this->flashMessages->savingErrorFlashMessage();
				$this->formUtils->recoverInputs($values);
			}
		}
	}

	/**
	 * Page for changing password
	 */
	public function renderPassword() { }

	/**
	 * Form for editing password
	 * @return Nette\Application\UI\Form
	 */
	public function createComponentEditPasswordForm() {
		$form = new Form;
		$form->addPassword("old", "Stávající heslo")
			->setRequired("Zadejte stávající heslo.");

		$form->addPassword("new1", "Nové heslo")
			->setRequired("Zadejte nové heslo.")
			->addRule(Form::MIN_LENGTH, "Heslo je příliš krátké. Minimální délka je %d znaků.", 5);

		$form->addPassword("new2", "Nové heslo znovu")
			->setRequired("Zadejte nové heslo.")
			->addRule(Form::EQUAL, "Hesla se musí shodovat.", $form["new1"]);

		$this->formUtils->manageUidToken($form, $this->editPassTokenName);

		$form->addSubmit("save", "Uložit");
		$form->onSuccess[] = [$this, "savePassword"];

		$this->formUtils->addFormProtection($form);
		$this->formUtils->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process saving new password
	 * @param  Form  $form
	 * @param  array $values array of values from the form
	 */
	public function savePassword(Form $form, $values) {
		$id = $this->getUser()->id;
		$uid = $values->uid;
		$t_name = $this->editPassTokenName;
		$editor = $this->editors->findById($id);

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			// verify password is ok
			if ($this->passwordVerify($values->old, $editor->password)) {
				$this->editors->updatePassword($values->new1, $id);
				$this->flashMessages->flashMessageSuccess("Heslo bylo úspěšně změněno.");
			} else {
				$this->flashMessages->flashMessageError("Stávající heslo nebylo zadáno správně.");
			}
		// problem with session
		} else {
			// action was performed, session is gone, but the data fits
			if ($this->passwordVerify($values->new1, $editor->password)) {
				$this->flashMessages->flashMessageSuccess("Heslo bylo úspěšně změněno.");
			// verify password was not ok
			} elseif ($this->passwordVerify($values->old, $editor->password)) {
				$this->flashMessages->flashMessageError("Stávající heslo nebylo zadáno správně.");
			// action was performed, session is gone and something is wrong
			} else {
				$this->flashMessages->savingErrorFlashMessage();
			}
		}
		$this->redirect("this");
	}

}
