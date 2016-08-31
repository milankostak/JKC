<?php

namespace App\Presenters;

use App\Model\Editor, App\Model\Blog;
use Nette\Application\UI\Form;

class EditorPresenter extends SecuredPresenter {

	private $editors, $blog;

	// used for "edit" and "delete" actions
	private $editor;

	private $editEditorTokenName = "editor_editing";
	private $addEditorTokenName = "editor_adding";
	private $deleteEditorTokenName = "editor_deleting";

	private $notFoundError = "Editor nebyl nalezen.";
	private $badNameError = "Editor s tímto loginem již existuje.";

	public function inject(Editor $editors, Blog $blog) {
		$this->editors = $editors;
		$this->blog = $blog;
	}

	protected function startup() {
		parent::startup();

		$this->blog = $this->blog->getBlogInfo();
		$this->template->big_title = $this->blog->name;

		$act = $this->getAction();
		if ($act == "edit" || $act == "delete") {
			$this->editor = $this->doesEditorExists($this->getParameter("id"));
		}
	}

	/**
	 * Check on startup if editor exists
	 * @param  number $id if of editor
	 * @return Nette\Database\Table\ActiveRow object with editor data if editor is found, redirect otherwise
	 */
	private function doesEditorExists($id) {
		$editor = $this->editors->findById($id);
		if (!$editor) {
			$this->flashMessage($this->notFoundError, "error");
			$this->redirect("default");
		} else {
			return $editor;
		}
	}

	/**
	 * Page with list of all editors
	 */
	public function renderDefault() {
		$this->template->editors = $this->editors->findAll();
	}

	/**
	 * Create new editor page
	 */
	public function renderAdd() { }

	/**
	 * Create form for adding new editor
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentAddEditorForm() {
		$form = new Form;

		$form->addText("name", "Jméno", 30)
			->setRequired("Vložte prosím jméno nového editora.")
			->addRule(Form::MAX_LENGTH, "Jméno je příliš dlouhé. Maximální délka je %d znaků.", 20);

		$form->addText("login", "Login", 30)
			->setRequired("Vložte prosím login nového editora.")
			->addRule(Form::MAX_LENGTH, "Login je příliš dlouhý. Maximální délka je %d znaků.", 20);

		$form->addPassword("password1", "Heslo")
			->setRequired("Zadejte nové heslo.")
			->addRule(Form::MIN_LENGTH, "Heslo je příliš krátké. Minimální délka je %d znaků.", 5);

		$form->addPassword("password2", "Heslo znovu")
			->setRequired("Zadejte nové heslo.")
			->addRule(Form::EQUAL, "Hesla se musí shodovat.", $form["password1"]);

		$form->addCheckBox("admin", "Přiřadit editorovi administrátorská práva.");

		$this->recoverData($form);
		$this->manageUidToken($form, $this->addEditorTokenName);

		$form->addSubmit("save", "Vytvořit editora");
		$form->onSuccess[] = [$this, "addEditor"];

		$this->addFormProtection($form);
		$this->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process creating new editor
	 * @param Form  $form
	 * @param array $values array of values from the form
	 */
	public function addEditor(Form $form, $values) {
		$uid = $values->uid;
		$t_name = $this->addEditorTokenName;
		$login = $values->login;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			// check for duplicities
			if ($this->editors->checkForDuplicates($login) == 0) {
				$this->editors->insert($values);
				$this->flashMessage("Editor byl úspěšně vytvořen.", "success");
				$this->redirect("default");
			} else {
				$this->flashMessage($this->badNameError, "error");
				$this->recoverInputs($values);
			}
		// problem with session
		} else {
			$editor = $this->editors->findByLogin($login);
			// action wasn't performed, session is gone
			if (!$editor) {
				$this->savingErrorFlashMessage();
				$this->recoverInputs($values);
			}
			$editor = $this->editors->findLast();
			// action was performed, session is gone, but the data fits
			if ($editor && $editor->name == $values->name && $this->passwordVerify($values->password1, $editor->password)
				&& $editor->login == $login && $editor->admin == $values->admin) {
				$this->flashMessage("Editor byl úspěšně vytvořen.", "success");
				$this->redirect("default");
			// action was performed, session is gone and something is wrong
			} else {
				$this->flashMessage($this->badNameError, "error");
				$this->recoverInputs($values);
			}
		}
	}

	/**
	 * Page with form for editing editor
	 * @param  number $id id of editor
	 */
	public function renderEdit($id) {
		$this->template->editor = $this->editor;
	}

	/**
	 * Creates form for editing editor
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentEditEditorForm() {
		$editor = $this->editor;
		$form = new Form;

		$form->addPassword("old", "Vložte své heslo")
			->setRequired("Zadejte své heslo.");

		$form->addText("name", "Jméno", 30)
			->setRequired("Vložte prosím jméno editora.")
			->addRule(Form::MAX_LENGTH, "Jméno je příliš dlouhé. Maximální délka je %d znaků.", 20)
			->setValue($editor->name);

		$form->addText("login", "Login", 30)
			->setRequired("Vložte prosím login editora.")
			->addRule(Form::MAX_LENGTH, "Login je příliš dlouhý. Maximální délka je %d znaků.", 20)
			->setValue($editor->login);

		$form->addPassword("password1", "Heslo")
			->setRequired(false)
			// min_length is not used, because then it would require filling the input everytime
			->addRule(Form::PATTERN, "Heslo je příliš krátké. Minimální délka je 5 znaků.", "^(.{5,}|)$");

		$form->addPassword("password2", "Heslo znovu")
			->setRequired(false)
			->addRule(Form::EQUAL, "Hesla se musí shodovat.", $form["password1"]);

		$form->addCheckBox("admin", "Přiřadit editorovi administrátorská práva.")
			->setValue($editor->admin);

		$this->recoverData($form);
		$this->manageUidToken($form, $this->editEditorTokenName);

		$form->addSubmit("save", "Uložit editora");
		$form->onSuccess[] = [$this, "editEditor"];

		$this->addFormProtection($form);
		$this->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process editing editor
	 * @param  Form   $form
	 * @param  array $values array of values from the form
	 */
	public function editEditor(Form $form, $values) {
		$id = $this->getParameter("id");
		$uid = $values->uid;
		$t_name = $this->editEditorTokenName;
		$login = $values->login;

		// info about logged editor
		$loggedUser = $this->editors->findById($this->getUser()->id);
		// verify password is ok
		if ($this->passwordVerify($values->old, $loggedUser->password)) {
			// session is ok
			if ($this->getSession($t_name)[$uid] == $uid) {
				if ($this->editors->checkForDuplicatesWithId($login, $id) == 0) {
					unset($this->getSession($t_name)[$uid]);
					$this->editors->update($values, $id);
					// change password only if the input was filled
					if ($values->password1 != "") $this->editors->updatePassword($values->password1, $id);
					$this->flashMessage("Editor byl úspěšně uložen.", "success");
					$this->redirect("default");
				} else {
					$this->flashMessage($this->badNameError, "error");
					$this->recoverInputs($values);
				}
			// problem with session
			} else {
				$editor = $this->editor;
				// action was performed, session is gone, but the data fits
				if ($editor->name == $values->name && $editor->login == $values->login && $editor->admin == $values->admin) {
					$this->flashMessage("Editor byl úspěšně uložen.", "success");
					$this->redirect("default");
				// action was performed, session is gone and something is wrong
				} else {
					$this->savingErrorFlashMessage();
					$this->recoverInputs($values);
				}
			}
		} else {
			$this->flashMessage("Vaše heslo nebylo zadáno správně.", "error");
			$this->redirect("this");
		}
	}

	/**
	 * Page for deleting editor
	 * @param  number $id id of editor
	 */
	public function renderDelete($id) {
		// deleting user performing the action cannot be deleted, user cannot delete himself
		if ($this->getUser()->id != $id) {
			$this->template->editor = $this->editor;
		} else {
			$this->flashMessage("Nelze smazat vlastní účet.", "error");
			$this->redirect("default");
		}
	}

	/**
	 * Create form for confirmation of deletion
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentDeleteEditorForm() {
		$form = new Form;

		$form->addPassword("password", "Vložte své heslo")
			->setRequired("Zadejte své heslo.");

		$form->addSubmit("save", "Smazat");
		$form->onSuccess[] = [$this, "deleteEditor"];

		$this->manageUidToken($form, $this->deleteEditorTokenName);

		$this->addFormProtection($form);
		$this->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process deleting editor
	 * @param  Form  $form
	 * @param  array $values array of values from the form
	 */
	public function deleteEditor(Form $form, $values) {
		$id = $this->getParameter("id");
		$uid = $values->uid;
		$t_name = $this->deleteEditorTokenName;
		$name = $this->editor->name;

		// it is not possible to delete own account
		if ($id != $this->getUser()->id) {
			// info about logged user
			$loggedUser = $this->editors->findById($this->getUser()->id);
			// verify password is ok
			if ($this->passwordVerify($values->password, $loggedUser->password)) {
				// session is ok
				if ($this->getSession($t_name)[$uid] == $uid) {
					unset($this->getSession($t_name)[$uid]);
					$this->editors->delete($id);
					$this->flashMessage("Editor '$name' byl úspěšně smazán.", "success");
					$this->redirect("default");	
				// problem with session
				} elseif ($this->editors->findById($id) != null) {
					$this->flashMessage("Při mazání se vyskytla chyba. Zopakujte prosím akci.", "error");
					$this->redirect("this");
				}
			} else {
				$this->flashMessage("Bylo zadáno chybné heslo.", "error");
				$this->redirect("this");
			}
		} else {
			$this->flashMessage("Nelze smazat vlastní účet.", "error");
			$this->redirect("default");
		}
	}

}
