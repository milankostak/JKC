<?php

namespace App\Presenters;

use App\Model\Poll, App\Model\Article, App\Model\Blog;
use Nette\Application\UI\Form;

class PollPresenter extends SecuredPresenter {

	private $blog, $polls, $articles;

	private $poll;

	private $addPollTokenName = "poll_adding";
	private $editPollTokenName = "poll_editing";
	private $deletePollTokenName = "poll_deleting";

	private $pollNotFoundError = "Anketa nebyla nalezena.";

	public function inject(Poll $polls, Article $articles, Blog $blog) {
		$this->polls = $polls;
		$this->articles = $articles;
		$this->blog = $blog;
	}

	protected function startup() {
		parent::startup();

		$this->template->big_title = $this->blog->getBlogInfo()->name;

		$act = $this->getAction();
		if ($act == "edit" || $act == "detail" || $act == "articles" || $act == "delete" || $act == "addOption") {
			$this->poll = $this->doesItemExist($this->getParameter("id"));
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
			$this->redirect("default");
		} else {
			return $poll;
		}
	}

	/**
	 * Render default page with list of all polls
	 */
	public function renderDefault() {
		$this->template->polls = $this->polls->findAllWithCount();
	}

	/**
	 * Page for creating new poll
	 */
	public function renderAdd() { }

	/**
	 * Form for creating new poll
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentAddPollForm() {
		$form = new Form;

		$form->addText("question", "Otázka", 40)
			->setRequired("Vložte prosím otázku pro novou anketu.")
			->addRule(Form::MAX_LENGTH, "Vložená otázka je příliš dlouhá. Maximální délka je %d znaků.", 50);

		$this->formUtils->recoverData($form);
		$this->formUtils->manageUidToken($form, $this->addPollTokenName);

		$form->addSubmit("save", "Vytvořit anketu");
		$form->onSuccess[] = [$this, "addPoll"];

		$this->formUtils->addFormProtection($form);
		$this->formUtils->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process adding of new poll
	 * @param Form  $form
	 * @param array $values array of values from the form
	 */
	public function addPoll(Form $form, $values) {
		$uid = $values->uid;
		$t_name = $this->addPollTokenName;
		$question = $values->question;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			$id = $this->polls->insert($question);
			$this->flashMessages->flashMessageSuccess("Anketa byla úspěšně vytvořena.");
			$this->redirect("detail", $id);
		// problem with session
		} else {
			$poll = $this->polls->findLast();
			// action was performed, session is gone, but the question fits
			if ($poll->question == $question) {
				$this->flashMessages->flashMessageSuccess("Anketa byla úspěšně vytvořena.");
				$this->redirect("detail", $poll->id_poll);
			// action was performed, session is gone and question is wrong
			} else {
				$this->flashMessages->savingErrorFlashMessage();
				$this->formUtils->recoverInputs($values);
			}
		}
	}

	/**
	 * Page for editing poll
	 * @param  number $id if of a poll
	 */
	public function renderEdit($id) {
		$this->template->poll = $this->poll;
	}

	/**
	 * Form for editing poll
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentEditPollForm() {
		$form = new Form;

		$form->addText("question", "Otázka", 40)
			->setRequired("Vložte prosím otázku pro novou anketu.")
			->addRule(Form::MAX_LENGTH, "Vložená otázka je příliš dlouhá. Maximální délka je %d znaků.", 50)
			->setValue($this->poll->question);

		$this->formUtils->recoverData($form);
		$this->formUtils->manageUidToken($form, $this->editPollTokenName);

		$form->addSubmit("save", "Uložit anketu");
		$form->onSuccess[] = [$this, "editPoll"];

		$this->formUtils->addFormProtection($form);
		$this->formUtils->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process editing poll
	 * @param  Form  $form
	 * @param  array $values array of values from the form
	 */
	public function editPoll(Form $form, $values) {
		$id = $this->getParameter("id");
		$uid = $values->uid;
		$t_name = $this->editPollTokenName;
		$question = $values->question;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			$this->polls->update($question, $id);
			$this->flashMessages->flashMessageSuccess("Anketa byla úspěšně upravena.");
			$this->redirect("detail", $id);
		// problem with session
		} else {
			$poll = $this->poll;
			// action was performed, session is gone, but the question fits
			if ($poll->question == $question) {
				$this->flashMessages->flashMessageSuccess("Anketa byla úspěšně upravena.");
				$this->redirect("detail", $id);
			// action was performed, session is gone and question is wrong
			} else {
				$this->flashMessages->savingErrorFlashMessage();
				$this->formUtils->recoverInputs($values);
			}
		}

	}

	/**
	 * Page for deleting poll
	 * @param  number $id id of a poll
	 */
	public function renderDelete($id) {
		$this->template->poll = $this->poll;
	}

	/**
	 * Form for confirmation of deleting
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentDeletePollForm() {
		$form = new Form;
		$this->formUtils->createOkCancelForm($form, $this, "formCancelled", "deletePoll");
		$this->formUtils->manageUidToken($form, $this->deletePollTokenName);
		return $form;
	}

	/**
	 * Process deleting of poll
	 * @param  Object $button
	 */
	public function deletePoll($button) {
		$values = $button->getForm()->getValues();
		$id = $this->getParameter("id");
		$t_name = $this->deletePollTokenName;
		$uid = $values->uid;
		$question = $this->poll->question;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			$this->polls->delete($id);
			$this->flashMessages->flashMessageSuccess("Anketa '$question' byla úspěšně smazána.");
			$this->redirect("default");
		// problem with session
		// poll is still there
		} elseif ($this->polls->findById($id) != null) {
			$this->flashMessages->flashMessageError("Při mazání se vyskytla chyba. Zopakujte prosím akci.");
			$this->redirect("this");
		// problem with session, but according to id, there is nothing
		} else {
			$this->flashMessages->flashMessageSuccess("Anketa '$question' byla úspěšně smazána.");
			$this->redirect("default");
		}
	}

	/**
	 * Process cancelation of deleting poll
	 */
	public function formCancelled() {
		$this->redirect("detail", $this->getParameter("id"));
	}

	/**
	 * Render page with poll detail, showing 
	 * @param  number $id id of a poll
	 */
	public function renderDetail($id) {
		$this->template->poll = $this->poll;
		$this->template->options = $this->polls->findAllPollOptions($id);
	}

	/**
	 * Render page with list of articles that have assigned given poll
	 * @param  number $id id of a poll
	 */
	public function renderArticles($id) {
		$this->template->poll = $this->poll;
		$this->template->articles = $this->articles->findAllArticlesByPollId($id);
	}

	/**
	 * Action that handles removing poll from an article
	 * @param  number $article id of an article
	 * @param  number $poll    id of a poll
	 */
	public function actionDeletePoll($article, $poll) {
		$article = $this->articles->findById($article);
		if (!$article) {
			$this->flashMessages->flashMessageError("Článek nebyl nalezen.");
			$this->redirect("default");
		} else {
			$this->doesItemExist($poll);

			$num = $this->articles->deletePollFromArticle($article);
			if ($num == 0) {
				$this->flashMessages->flashMessageError("Tato anketa není přiřazena tomuto článku.");
			} else {
				$this->flashMessages->flashMessageSuccess("Anketa byla článku úspěšně odebrána.");
			}
			$this->redirect("articles", $poll);
		}
	}

}
