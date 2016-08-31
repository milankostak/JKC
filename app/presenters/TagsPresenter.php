<?php

namespace App\Presenters;

use App\Model\Tag, App\Model\Article, App\Model\Blog;
use Nette\Application\UI\Form;
use Nette\Utils\Strings;

class TagsPresenter extends SecuredPresenter {

	private $tags, $articles, $blog;

	// every action except "add"
	private $tag;

	private $deleteTagTokenName = "tag_deleting";
	private $addTagTokenName = "tag_adding";
	private $editTagTokenName = "tag_editing";

	private $notFoundError = "Štítek nebyl nalezen.";
	private $badNameError = "Štítek s tímto názvem již existuje.";

	public function inject(Tag $tags, Article $articles, Blog $blog) {
		$this->tags = $tags;
		$this->blog = $blog;
		$this->articles = $articles;
	}

	protected function startup() {
		parent::startup();
		$this->template->big_title = $this->blog->getBlogInfo()->name;

		$act = $this->getAction();
		if ($act != "add" && $act != "default") {
			$param = ($act == "deleteTag") ? "tag" : "id";
			$this->tag = $this->doesTagExists($this->getParameter($param));
		}
	}

	/**
	 * Check if the tag exists on startup
	 * Call only when there is supposed to be one
	 * @param  number $id id of a tag
	 * @return Nette\Database\Table\ActiveRow object with tag data if tag is found, redirect otherwise
	 */
	private function doesTagExists($id) {
		$tag = $this->tags->findById($id);
		if (!$tag) {
			$this->flashMessage($this->notFoundError, "error");
			$this->redirect("default");
		} else {
			return $tag;
		}
	}

	/**
	 * Page with list of all tags
	 */
	public function renderDefault() {
		$this->template->tags = $this->tags->findAllWithCount();
	}

	/**
	 * Detail of tag
	 * @param  number $id id of a tag
	 */
	public function renderDetail($id) {
		$this->template->tag = $this->tag;
		$this->template->tags = $this->tags->findAllTagArticles($id);
	}

	/**
	 * Create new tag page
	 */
	public function renderAdd() { }

	/**
	 * Form for creating new tag
	 * @return Form
	 */
	protected function createComponentAddTagForm() {
		$form = new Form;

		$author = $form->addText("name", "Název", 20)
			->setRequired("Vložte prosím název pro nový štítek.")
			->addRule(Form::MAX_LENGTH, "Vložený název je příliš dlouhý. Maximální délka je %d znaků.", 10);

		$this->recoverData($form);
		$this->manageUidToken($form, $this->addTagTokenName);

		$form->addSubmit("save", "Vytvořit štítek");
		$form->onSuccess[] = [$this, "addTag"];

		$this->addFormProtection($form);
		$this->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process add tag form
	 * @param Form  $form
	 * @param array $values array of values from the form
	 */
	public function addTag(Form $form, $values) {
		$uid = $values->uid;
		$t_name = $this->addTagTokenName;
		$name = $values->name;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			// check for duplicates
			if ($this->tags->checkForDuplicates($name) == 0) {
				$id = $this->tags->insert($name);
				$this->flashMessage("Štítek byl úspěšně vytvořen.", "success");
				$this->redirect("detail", $id);
			} else {
				$this->flashMessage($this->badNameError, "error");
				$this->recoverInputs($values);
			}
		// problem with session
		} else {
			$tag = $this->tags->findByUrl(Strings::webalize($name));
			// action wasn't performed, session is gone
			if (!$tag) {
				$this->savingErrorFlashMessage();
				$this->recoverInputs($values);
			}
			$tag = $this->tags->findLast();
			// action was performed, session is gone, but the data fits
			if ($tag && $tag->url == Strings::webalize($name) && $tag->name == $name) {
				$this->flashMessage("Štítek byl úspěšně vytvořen.", "success");
				$this->redirect("detail", $tag->id_tag);
			// action was performed, session is gone and something is wrong
			} else {
				$this->flashMessage($this->badNameError, "error");
				$this->recoverInputs($values);
			}
		}
	}

	/**
	 * Edit tag
	 * @param  number $id id of tag
	 */
	public function renderEdit($id) {
		$this->template->tag = $this->tag;
	}

	/**
	 * Form for updating tag
	 * @return Form
	 */
	protected function createComponentEditTagForm() {
		$tag = $this->tag;

		$form = new Form;

		$author = $form->addText("name", "Název", 20)
			->setRequired("Vložte prosím název pro štítek.")
			->addRule(Form::MAX_LENGTH, "Vložený název je příliš dlouhý. Maximální délka je %d znaků.", 10)
			->setValue($tag->name);

		$this->recoverData($form);
		$this->manageUidToken($form, $this->editTagTokenName);

		$form->addSubmit("save", "Uložit štítek");
		$form->onSuccess[] = [$this, "editTag"];

		$this->addFormProtection($form);
		$this->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process edit tag form
	 * @param Form  $form
	 * @param array $values array of values from the form
	 */
	public function editTag(Form $form, $values) {
		$id = $this->getParameter("id");
		$uid = $values->uid;
		$t_name = $this->editTagTokenName;
		$name = $values->name;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			// check duplicity of url
			if ($this->tags->checkForDuplicatesWithId($name, $id) == 0) {
				$this->tags->update($name, $id);
				$this->flashMessage("Štítek byl úspěšně upraven.", "success");
				$this->redirect("default");
			} else {
				$this->flashMessage($this->badNameError, "error");
				$this->recoverInputs($values);
			}
		// problem with session
		} else {
			$tag = $this->tag;
			// action was performed, session is gone, but the data fits
			if ($tag->id_tag == $id && $tag->url == Strings::webalize($name) && $tag->name == $name) {
				$this->flashMessage("Štítek byl úspěšně upraven.", "success");
				$this->redirect("default");
			// check duplicity of url
			} elseif ($this->tags->checkForDuplicatesWithId($name, $id) == 0) {
				$this->flashMessage($this->badNameError, "error");
				$this->recoverInputs($values);
			// action was performed, session is gone and something is wrong
			} else {
				$this->savingErrorFlashMessage();
				$this->recoverInputs($values);
			}
		}
	}

	/**
	 * Delete tag
	 * @param  number $id id of tag
	 */
	public function renderDelete($id) {
		$this->template->tag = $this->tag;
	}

	/**
	 * Form for deleting tag
	 * @return Form
	 */
	protected function createComponentDeleteTagForm() {
		$form = new Form;
		$this->createOkCancelForm($form, $this, "formCancelled", "deleteTag");
		$this->manageUidToken($form, $this->deleteTagTokenName);
		return $form;
	}

	/**
	 * Process delete tag form
	 * @param Form  $form
	 * @param array $values array of values from the form
	 */
	public function deleteTag($button) {
		$values = $button->getForm()->getValues();
		$id = $this->getParameter("id");
		$uid = $values->uid;
		$t_name = $this->deleteTagTokenName;
		$name = $this->tag->name;

		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			$this->tags->delete($id);
		} elseif ($this->tag != null) {
			$this->savingErrorFlashMessage();
			$this->redirect("this");
		}
		$this->flashMessage("Štítek '$name' byl úspěšně smazán.", "success");
		$this->redirect("default");
	}

	/**
	 * Cancel delete tag form
	 */
	public function formCancelled() {
		$id = $this->getParameter("id");
		$this->redirect("detail", $id);
	}

	/**
	 * Action for deleting tag from article
	 * @param  number $article id of article
	 * @param  number $tag     id of tag
	 */
	public function actionDeleteTag($article, $tag) {
		$article = $this->articles->findById($article);
		if ($article == null) {
			$this->flashMessage("Článek nebyl nalezen.", "error");
			$this->redirect("default");
		} else {
			$num = $this->tags->deleteTagFromArticle($article, $tag);
			if ($num == 0) {
				$this->flashMessage("Tento štítek není přiřazen k tomuto článku.", "error");
			} else {
				$this->flashMessage("Štítek byl úspěšně odebrán.", "success");
			}
			$this->redirect("detail", $tag);
		}
	}

}
