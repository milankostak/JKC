<?php

namespace App\Presenters;

use App\Model\Blog;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;

class SignPresenter extends BasePresenter {

	private $blog;

	public function inject(Blog $blog) {
		$this->blog = $blog;
	}

	/**
	 * Redirect default to in
	 */
	public function actionDefault() {
		$this->redirect("in");
	}

	/**
	 * Page with login form, if already logged, then redirect to Article:default page
	 */
	public function actionIn() {
		if ($this->user->isLoggedIn()) {
			$this->redirect("Article:default");
		}
		$this->blog = $this->blog->getBlogInfo();
		$this->template->big_title = $this->blog->name;
		$this->template->small_title = $this->blog->sub_name;
	}

	/**
	 * Login form on Sign:in page
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm() { 
		$form = new Form();
		$form->addText("login", "Uživatelské jméno", 30, 20)
				->setRequired("Vložte prosím své uživatelské jméno.");
		$form->addPassword("password", "Heslo", 30)
				->setRequired("Vložte prosím své heslo.");
		$form->addSubmit("send", "Přihlásit se");
		$form->setDefaults(array(
			"login" => "M",
		));
		$form->onSuccess[] = [$this, "signInFormSubmitted"];
		$this->formUtils->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process sign in form
	 * @param  Form   $form
	 * @param  array $values values of the form
	 */
	public function signInFormSubmitted(Form $form, $values) {
		try {
			$user = $this->getUser();
			$user->login($values->login, $values->password);
			$this->flashMessages->flashMessageSuccess("Přihlášení proběhlo úspěšně.");
			$this->redirect("Article:default");
		} catch (AuthenticationException $e) {
			//$form->addError("Neplatné uživatelské jméno nebo heslo.");
			$this->flashMessages->flashMessageError("Neplatné uživatelské jméno nebo heslo.");
			return;
		}
	}

	/**
	 * Log out user and redirect to Sign:in page
	 */
	public function actionOut() {
		$this->getUser()->logout();
		$this->flashMessages->flashMessageSuccess("Odlášení proběhlo úspěšně.");
		$this->redirect("in");
	}

}
