<?php

namespace App\Presenters;

use App\Model\Option, App\Model\Poll, App\Model\Blog;
use Nette\Application\UI\Form;

class OptionPresenter extends SecuredPresenter {

	private $options, $polls, $blog;

	private $option, $poll;

	private $addOptionTokenName = "option_adding";
	private $editOptionTokenName = "option_editing";
	private $deleteOptionTokenName = "option_deleting";

	private $pollNotFoundError = "Anketa nebyla nalezena.";
	private $optionNotFoundError = "Odpověď nebyla nalezena.";

	public function inject(Option $options, Poll $polls, Blog $blog) {
		$this->options = $options;
		$this->polls = $polls;
		$this->blog = $blog;
	}

	protected function startup() {
		parent::startup();

		$this->blog = $this->blog->getBlogInfo();
		$this->template->big_title = $this->blog->name;
		$this->template->small_title = $this->blog->sub_name;

		$act = $this->getAction();
		if ($act == "add") {
			$this->poll = $this->doesItemExist($this->getParameter("id"));
		} elseif ($act == "edit" || $act == "delete") {
			$this->option = $this->doesOptionExist($this->getParameter("id"));
			$this->poll = $this->option->poll;
 		}
	}

	/**
	 * Check if poll exists
	 * @param  number $id id of poll
	 * @return Nette\Database\Table\ActiveRow object with poll data if poll is found, redirect otherwise
	 */
	private function doesItemExist($id) {
		$poll = $this->polls->findById($id);
		if (!$poll) {
			$this->flashMessages->flashMessageError($this->pollNotFoundError);
			$this->redirect("Poll:default");
		} else {
			return $poll;
		}
	}

	/**
	 * Check if option exists
	 * @param  number $id id of option
	 * @return Nette\Database\Table\ActiveRow object with option data if option is found, redirect otherwise
	 */
	private function doesOptionExist($id) {
		$option = $this->options->findById($id);
		if (!$option) {
			$this->flashMessages->flashMessageError($this->optionNotFoundError);
			$this->redirect("Poll:default");
		} else {
			return $option;
		}
	}

	/**
	 * Render page for adding new option to poll
	 * @param  number $id id of a poll
	 */
	public function renderAdd($id) {
		$this->template->poll = $this->poll;
	}

	/**
	 * Form for creating new option
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentAddOptionForm() {
		$form = new Form;

		$form->addText("answer", "Odpověď", 20)
			->setRequired("Vložte prosím novou odpověď pro anketu.")
			->addRule(Form::MAX_LENGTH, "Vložená odpověď je příliš dlouhá. Maximální délka je %d znaků.", 20);

		$this->formUtils->recoverData($form);
		$this->formUtils->manageUidToken($form, $this->addOptionTokenName);

		$form->addSubmit("save", "Vytvořit odpověď");
		$form->onSuccess[] = [$this, "addOption"];

		$this->formUtils->addFormProtection($form);
		$this->formUtils->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process adding of new option
	 * @param Form   $form
	 * @param array $values array of values from the form
	 */
	public function addOption(Form $form, $values) {
		$poll = $this->getParameter("id");
		$uid = $values->uid;
		$t_name = $this->addOptionTokenName;
		$answer = $values->answer;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			$this->options->insert($answer, $poll);
			$this->flashMessages->flashMessageSuccess("Odpověď byla úspěšně přidána.");
			$this->redirect("Poll:detail", $poll);
		// problem with session
		} else {
			$option = $this->options->findLast();
			// action was performed, session is gone, but the answer fits
			if ($option->answer == $answer) {
				$this->flashMessages->flashMessageSuccess("Odpověď byla úspěšně přidána.");
				$this->redirect("Poll:detail", $poll);
			// action was performed, session is gone and answer is wrong
			} else {
				$this->flashMessages->savingErrorFlashMessage();
				$this->formUtils->recoverInputs($values);
			}
		}
	}

	/**
	 * Render page for editing option
	 * @param  number $id id of an option
	 */
	public function renderEdit($id) {
		$this->template->poll = $this->poll;
		$this->template->option = $this->option;
	}

	/**
	 * Create form for editing option
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentEditOptionForm() {
		$form = new Form;

		$form->addText("answer", "Odpověď", 20)
			->setRequired("Vložte prosím novou odpověď pro anketu.")
			->addRule(Form::MAX_LENGTH, "Vložená odpověď je příliš dlouhá. Maximální délka je %d znaků.", 20)
			->setValue($this->option->answer);

		$this->formUtils->recoverData($form);
		$this->formUtils->manageUidToken($form, $this->editOptionTokenName);

		$form->addSubmit("save", "Uložit odpověď");
		$form->onSuccess[] = [$this, "editOption"];

		$this->formUtils->addFormProtection($form);
		$this->formUtils->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process editing option
	 * @param  Form  $form
	 * @param  array $values array of values from the form
	 */
	public function editOption(Form $form, $values) {
		$option = $this->getParameter("id");
		$poll = $this->poll->id_poll;

		$answer = $values->answer;
		$t_name = $this->editOptionTokenName;
		$uid = $values->uid;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			$this->options->update($answer, $option);
			$this->flashMessages->flashMessageSuccess("Odpověď byla úspěšně upravena.");
			$this->redirect("Poll:detail", $poll);
		// problem with session
		} else {
			// action was performed, session is gone, but the answer fits
			if ($this->option->answer == $answer) {
				$this->flashMessages->flashMessageSuccess("Odpověď byla úspěšně upravena.");
				$this->redirect("Poll:detail", $poll);
			// action was performed, session is gone and answer is wrong
			} else {
				$this->flashMessages->savingErrorFlashMessage();
				$this->formUtils->recoverInputs($values);
			}
		}
	}

	/**
	 * Render page for confirmation of deleting of an option
	 * @param  number $id id of an option
	 */
	public function renderDelete($id) {
		$this->template->poll = $this->poll;
		$this->template->option = $this->option;
	}

	/**
	 * Create confirmation form
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentDeleteOptionForm() {
		$form = new Form;
		$this->formUtils->createOkCancelForm($form, $this, "formCancelledOption", "deleteOption");
		$this->formUtils->manageUidToken($form, $this->deleteOptionTokenName);
		return $form;
	}

	/**
	 * Process deleting of option
	 * @param  object $button
	 */
	public function deleteOption($button) {
		$values = $button->getForm()->getValues();
		$option = $this->getParameter("id");
		$uid = $values->uid;
		$answer = $this->option->answer;
		$poll = $this->poll->id_poll;

		// session is ok
		if ($this->getSession($this->deleteOptionTokenName)[$uid] == $uid) {
			unset($this->getSession($this->deleteOptionTokenName)[$uid]);
			$this->options->delete($option);
			$this->flashMessages->flashMessageSuccess("Odpověď '$answer' byla úspěšně smazána.");
			$this->redirect("Poll:detail", $poll);
		// problem with session
		// option is still there
		} elseif ($this->option != null) {
			$this->flashMessages->savingErrorFlashMessage();
			$this->redirect("this");
		// problem with session, but according to id, there is nothing
		} else {
			$this->flashMessages->flashMessageSuccess("Odpověď '$answer' byla úspěšně smazána.");
			$this->redirect("Poll:detail", $poll);
		}
	}

	/**
	 * Process cancelation of the form
	 */
	public function formCancelledOption() {
		$this->redirect("Poll:detail", $this->getParameter("poll"));
	}

}