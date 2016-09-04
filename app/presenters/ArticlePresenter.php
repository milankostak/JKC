<?php

namespace App\Presenters;

use App\Model\Article, App\Model\Comment, App\Model\Poll, App\Model\Tag, App\Model\Blog;
use Nette\Application\UI\Form;

class ArticlePresenter extends SecuredPresenter {

	const TEXT_TEXTAREA_COLS = 80;
	const TEXT_TEXTAREA_ROWS = 20;

	const PEREX_TEXTAREA_COLS = 80;
	const PEREX_TEXTAREA_ROWS = 10;

	private $articles, $comments, $polls, $tags, $blog;

	// used for "edit", "delete", "show" and "publish" actions
	private $article;

	private $notFoundError = "Článek nebyl nalezen.";
	private $tagNotFoundError = "Štítek nebyl nalezen.";
	private $badTitleError = "Článek s tímto názvem již existuje. Upravte název a zopakujte akci.";
	private $badUrlError = "Článek s touto URL již existuje. Změňte název tak, aby vygenerovaná URL byla jedinečná.";

	private $addTokenName = "add_article";
	private $editTokenName = "edit_article";
	private $deleteTokenName = "delete_article";
	private $publishTokenName = "publish_article";

	public function inject(Article $articles, Comment $comments, Poll $polls, Tag $tags, Blog $blog) {
		$this->articles = $articles;
		$this->comments = $comments;
		$this->polls = $polls;
		$this->tags = $tags;
		$this->blog = $blog;
	}

	protected function startup() {
		parent::startup();

		$this->blog = $this->blog->getBlogInfo();
		$this->template->big_title = $this->blog->name;

		$action = $this->getAction();
		if ($action == "edit" || $action == "show" || $action == "publish" || $action == "delete") {
			$this->article = $this->doesArticleExists($this->getParameter("id"));
		}
		if ($action == "edit" || $action == "delete") {
			$this->checkUser($this->article->id_editor);
		}
		if ($action != "add" && $action != "edit" && $action != "addTag" && $action != "deleteTag") {
			 $this->prepareArticlesMenu();
		}
	}

	/**
	 * Check if the article exists on startup
	 * @param  number $id id of an article
	 * @return Nette\Database\Table\ActiveRow object with article data if article is found, redirect otherwise
	 */
	private function doesArticleExists($id) {
		$article = $this->articles->findById($id);
		if (!$article) {
			$this->flashMessages->flashMessageError($this->notFoundError);
			$this->redirect("default");
		} else {
			return $article;
		}
	}

	/**
	 * Check if the tag exists
	 * @param  number $id id of a tag
	 * @return Nette\Database\Table\ActiveRow object with tag data if tag is found, redirect otherwise
	 */
	private function doesTagExists($article, $id) {
		$tag = $this->tags->findById($id);
		if (!$tag) {
			$this->flashMessages->flashMessageError($this->tagNotFoundError);
			$this->redirect("tags", $article);
		} else {
			return $tag;
		}
	}

	/**
	 * Chechk if user access to given sections
	 * @param  number $id_editor id of an editor
	 */
	private function checkUser($id_editor) {
		if ($this->getUser()->roles["Admin"] != "1" && $this->getUser()->id != $id_editor) {
			$this->flashMessages->flashMessageAuthentification("Tento článek nemůžete upravovat.");
			$this->redirect("default");
		}
	}

	/**
	 * Prepare left side article menu with years and months categories
	 */
	private function prepareArticlesMenu() {
		$articles = $this->articles->findAllNonDraftsArticles();
		$all = [];
		foreach ($articles as $a) {
			$y = date("Y", $a->date);
			$m = date("m", $a->date);
			if (isset($all[$y])) {
				if (isset($all[$y][$m])) {
					$all[$y][$m]++;
				} else {
					$all[$y][$m] = 1;
				}
			} else {
				$all[$y] = [];
				$all[$y][$m] = 1;
			}
		}
		$this->template->years = $all;
	}

	/**
	 * Render default empty page, just with left side menu
	 */
	public function renderDefault() { }

	/**
	 * Render page for creating new article
	 */
	public function renderAdd() { }

	/**
	 * Render page with list of draft articles
	 */
	public function renderDrafts() {
		$this->template->drafts = $this->articles->findAllDrafts();
	}

	/**
	 * Render category page with list of articles for given year and month
	 * @param  number $year
	 * @param  number $month
	 */
	public function renderCategory($year, $month) {
		$start_date = $year."-".$month."-01";
		$start_time = $start_date." 00:00:00";

		$max_day = date("t", strtotime($start_date));
		$end_time = $year."-".$month."-".$max_day." 23:59:59";

		$start_time = new \DateTime($start_time);
		$end_time = new \DateTime($end_time);

		$this->template->articles = $this->articles->findAllNonDraftArticlesByTimeInterval($start_time->getTimestamp(), $end_time->getTimestamp());
	}

	/**
	 * Render page with article preview
	 * @param  number $id id of an article
	 */
	public function renderShow($id) {
		$this->template->article = $this->article;
	}

	/**
	 * Render page for editing an article
	 * @param  number $id id of an article
	 */
	public function renderEdit($id) {
		$this->template->article = $this->article;
	}

	/**
	 * Render page for managing comments for given article
	 * @param  number $id id of an article
	 */
	public function renderComments($id) {
		$article = $this->doesArticleExists($id);
		if ($this->getParameter("deleted") == "1") {
			$this->template->comments = $this->comments->findAllCommentsByArticleId($id);
		} else {
			$this->template->comments = $this->comments->findNonDeletedCommentsByArticleId($id);
		}
		$this->template->article = $article;
	}

	/**
	 * Render page for managing tag for given article
	 * @param  number $id id of an article
	 */
	public function renderTags($id) {
		$this->template->article = $this->doesArticleExists($id);
		$this->template->article_tags = $this->tags->findAllArticleTags($id);
		$this->template->nontags = $this->tags->findAllArticleNonTags($id);
	}

	/**
	 * Delete tag from the article
	 * @param  number $article id of an article
	 * @param  number $tag     id of a tag
	 */
	public function actionDeleteTag($article, $tag) {
		$this->doesArticleExists($article);
		$this->doesTagExists($article, $tag);

		$num = $this->tags->deleteTagFromArticle($article, $tag);
		if ($num == 0) {
			$this->flashMessages->flashMessageError("Tento štítek není přiřazen k tomuto článku.");
		} else {
			$this->flashMessages->flashMessageSuccess("Štítek byl úspěšně odebrán.");
		}
		$this->redirect("tags", $article);
	}

	/**
	 * Add tag to the article
	 * @param  number $article id of an article
	 * @param  number $tag     id of a tag
	 */
	public function actionAddTag($article, $tag) {
		$this->doesArticleExists($article);
		$this->doesTagExists($article, $tag);

		if ($this->tags->isArticleHavingTag($article, $tag)) {
			$this->flashMessages->flashMessageError("Tento článek již tento štítek má.");
		} else {
			$this->tags->addTagToArticle($article, $tag);
			$this->flashMessages->flashMessageSuccess("Štítek byl úspěšně přidán.");
		}
		$this->redirect("tags", $article);
	}

	/**
	 * Render page with form for publishing article
	 * @param  number $id id of an article
	 */
	public function renderPublish($id) {
		if ($this->article->draft == 0) {
			$this->flashMessages->flashMessageError("Tento článek je již zveřejněn.");
			$this->redirect("show", $id);
		} else {	
			$this->template->article = $this->article;
			$this->prepareArticlesMenu();
		}
	}

	/**
	 * Form for immediate publish
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentImmediatePublishForm() {
		$form = new Form;

		$form->addSubmit("save", "Zveřejnit článek ihned");
		$form->onSuccess[] = [$this, "publishArticleNow"];

		$this->formUtils->addFormProtection($form);
		$form->getRenderer()->wrappers['control']['.submit'] = 'btn btn-primary';
		return $form;
	}


	/**
	 * Manage publish article now
	 * @param  Form   $form
	 * @param  array $values array of values from the form
	 */
	public function publishArticleNow(Form $form, $values) {
		$id = $this->getParameter("id");
		if ($this->article->draft == 1) {
			$this->articles->publish($id, time());
			$this->flashMessages->flashMessageSuccess("Článek byl úspěšně zveřejněn.");
		} else {
			$this->flashMessages->flashMessageError("Tento článek je již zveřejněn.");
		}
		$this->redirect("show", $id);
	}

	/**
	 * Form for filling publish date
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentPublishForm() {
		$form = new Form;

		$form->addText("datetime", "Datum a čas ve formátu 'dd.mm.rrrr hh:mm'")
			->setRequired(false)
			->addRule(Form::PATTERN, "Neplatný formát data a času. Správný formát je 'dd.mm.rrrr hh:mm'.", "^([0-9]{2}\.[0-9]{2}\.[0-9]{4} [0-9]{2}:[0-9]{2})$")
			->setValue(date("d.m.Y H:i", time()));
		
		$this->formUtils->recoverData($form);
		$this->formUtils->manageUidToken($form, $this->publishTokenName);

		$form->addSubmit("save", "Zveřejnit článek v zadaný čas");
		$form->onSuccess[] = [$this, "publishArticle"];

		$this->formUtils->addFormProtection($form);
		$this->formUtils->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Manage publish article
	 * @param  Form   $form
	 * @param  array $values array of values from the form
	 */
	public function publishArticle(Form $form, $values) {
		$id = $this->getParameter("id");
		$uid = $values->uid;
		$t_name = $this->publishTokenName;

		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			if ($this->article->draft == 1) {
				$time = $values->datetime;
				$time = new \DateTime($time.":00");
				$time = $time->getTimestamp();
				if ($time == 0) {
					$this->flashMessages->flashMessageError("Zvolené datum je neplatné.");
					$this->formUtils->recoverInputs($values);
				} elseif ($time < time()) {
					$this->flashMessages->flashMessageError("Zvolené datum a čas jsou v minulosti.");
					$this->formUtils->recoverInputs($values);
				} else {
					$this->articles->publish($id, $time);
					$this->flashMessages->flashMessageSuccess("Uloženo. Článek bude přístupný v zadaný čas.");
					$this->redirect("show", $id);
				}
			} else {
				$this->flashMessages->flashMessageError("Tento článek je již zveřejněn.");
				$this->redirect("show", $id);
			}
		}
	}

	/**
	 * Render page with confirmation form for deleting
	 * @param  number $id id of an article
	 */
	public function renderDelete($id) {
		$this->template->article = $this->article;
	}

	/**
	 * Form for confirmation
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentDeleteArticleForm() {
		$form = new Form;
		$this->formUtils->createOkCancelForm($form, $this, "formCancelled", "deleteArticle");
		$this->formUtils->manageUidToken($form, $this->deleteTokenName);
		return $form;
	}

	/**
	 * Process deleting of an article
	 */
	public function deleteArticle($button) {
		$values = $button->getForm()->getValues();
		$id = $this->getParameter("id");
		$uid = $values->uid;
		$t_name = $this->deleteTokenName;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			$this->articles->delete($id);
			$this->flashMessages->flashMessageSuccess("Článek byl úspěšně smazán.");
			$this->redirect("default");
		// problem with session
		// article is still there
		} elseif ($this->articles->findById($id) != null) {
			$this->flashMessages->flashMessageError("Při mazání se vyskytla chyba. Zopakujte prosím akci.");
			$this->redirect("this");
		// problem with session, but according to id, there is nothing
		} else {
			$this->flashMessages->flashMessageSuccess("Článek byl úspěšně smazán.");
			$this->redirect("default");
		}
	}

	/**
	 * Cancel deleting of an article
	 */
	public function formCancelled() {
		$this->redirect("show", $this->getParameter("id"));
	}

	/**
	 * Form for editing an article
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentEditArticleForm() {
		$article = $this->article;

		$form = new Form;
		$form->addText("title", "Název článku", 50)
			->setRequired("Vložte prosím název článku.")
			->addRule(Form::MAX_LENGTH, "Název článku je příliš dlouhý. Maximální délka je %d znaků.", 50)
			->setValue($article->title);

		$form->addTextArea("perex", "Perex")
			->setRequired("Vložte prosím perex.")
			->addRule(Form::MAX_LENGTH, "Perex je příliš dlouhý. Maximální délka je %d znaků.", 500)
			->setAttribute("cols", self::PEREX_TEXTAREA_COLS)
			->setAttribute("rows", self::PEREX_TEXTAREA_ROWS)
			->setValue($article->perex);

		$form->addCheckBox("social", "Zobrazit u tohoto článku tlačítka sociálních sítí")
			->setValue($article->social);

		$form->addCheckBox("comments", "Povolit u tohoto článku zobrazení starých a přidávání nových komentářů")
			->setValue($article->comments);

		$form->addSelect("poll", "Anketa", $this->polls->findAllPollsAsArray())
			->setPrompt("Vyberte anketu")
			->setValue($article->id_poll);

		$form->addTextArea("text")
			->setRequired(false)
			->addRule(Form::MAX_LENGTH, "Text je příliš dlouhý. Maximální délka je 20 000 znaků.", 20000)
			->setAttribute("cols", self::TEXT_TEXTAREA_COLS)
			->setAttribute("rows", self::TEXT_TEXTAREA_ROWS)
			->setAttribute("class", "tinymce")
			->setValue($article->text);

		$this->formUtils->recoverData($form);
		$this->formUtils->manageUidToken($form, $this->editTokenName);

		if ($article->draft == 1) {
			$form->addSubmit("publish_now", "Hned zveřejnit")
				->onClick[] = [$this, "saveAndPublishDraft"];

			$form->addSubmit("save_on_time", "Určit datum vydání")
				->onClick[] = [$this, "saveArticleWithTime"];

			$form->addSubmit("save_draft", "Uložit do rozepsaných")
				->onClick[] = [$this, "saveDraft"];

			$form->addSubmit("save_n_continue", "Uložit a psát dál")
				->setAttribute("class", "ajax")
				->onClick[] = [$this, "saveAndContinue"];
		} else {
			$form->addSubmit("save", "Uložit")
				->onClick[] = [$this, "saveArticle"];

			$form->addSubmit("save_n_continue", "Uložit a psát dál")
				->setAttribute("class", "ajax")
				->onClick[] = [$this, "saveAndContinue"];
		}

		$this->formUtils->addFormProtection($form);
		$this->formUtils->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process editing an article
	 * @param  array  $values         array of values from the form
	 * @param  string $messageSuccess message for situation if editing is sucessful
	 * @param  string $target         target page after process is successfully finished
	 * @param  number $publish        holds information if the article should be published
	 */
	private function manageSavingOld($values, $messageSuccess, $target, $publish) {
		$id = $this->getParameter("id");
		$uid = $values->uid;
		$t_name = $this->editTokenName;
		$ajax = ($this->isAjax() && $target == "edit#tinymce");

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			// not setting new uid for ajax, old is enough
			if (!$ajax) unset($this->getSession($t_name)[$uid]);
			// check duplicity of title
			if ($this->articles->isOldArticleTitleDuplicate($values->title, $id)) {
				if ($ajax) {
					$this->template->ajaxMessage = ["status" => "error ajax", "message" => $this->badTitleError];
					$this->redrawControl("ajaxSaveArticle");
				} else {
					$this->flashMessages->flashMessageError($this->badTitleError);
					$this->formUtils->recoverInputs($values);
				}
			// check duplicity of url
			} elseif ($this->articles->isOldArticleUrlDuplicate($values->title, $id)) {
				if ($ajax) {
					$this->template->ajaxMessage = ["status" => "error ajax", "message" => $this->badUrlError];
					$this->redrawControl("ajaxSaveArticle");
				} else {
					$this->flashMessages->flashMessageError($this->badUrlError);
					$this->formUtils->recoverInputs($values);
				}
			// success, save and redirect, custom code for every situation
			} else {
				$this->articles->edit($values, $id);
				if ($publish) {
					$this->articles->publish($id, time());
				}
				if ($ajax) {
					$this->template->ajaxMessage = ["status" => "success ajax", "message" => $messageSuccess];
					$this->redrawControl("ajaxSaveArticle");
				} else {
					$this->flashMessages->flashMessageSuccess($messageSuccess);
					$this->redirect($target, $id);
				}
			}
		// problem with session
		} else {
			$art = $this->article;
			// action was performed, session is gone, but the data fits
			if ($art->title == $values->title      && $art->perex == $values->perex
				&& $art->social == $values->social && $art->comments == $values->comments
				&& $art->id_poll == $values->poll  && $art->text == $values->text) {
				$this->flashMessages->flashMessageSuccess($messageSuccess);
				$this->redirect($target, $id);
			// repeat warnings about duplicities
			} elseif ($this->articles->isOldArticleTitleDuplicate($values->title, $id)) {
				$this->flashMessages->flashMessageError($this->badTitleError);
				$this->formUtils->recoverInputs($values);
			} elseif ($this->articles->isOldArticleUrlDuplicate($values->title, $id)) {
				$this->flashMessages->flashMessageError($this->badUrlError);
				$this->formUtils->recoverInputs($values);
			// action was performed, session is gone and something is wrong
			} else {
				$this->flashMessages->savingErrorFlashMessage();
				$this->formUtils->recoverInputs($values);
			}
		}
	}


	/**
	 * Save and publish
	 * @param  object $button
	 */
	public function saveAndPublishDraft($button) {
		$values = $button->getForm()->getValues();
		$this->manageSavingOld($values, "Článek byl úspěšně zveřejněn.", "show", 1);
	}

	/**
	 * Save as draft and continue to apge with publish date selection
	 * @param  object $button
	 */
	public function saveArticleWithTime($button) {
		$values = $button->getForm()->getValues();
		$this->manageSavingOld($values, "Článek byl úspěšně uložen.", "publish", 0);
	}

	/**
	 * Save draft
	 * @param  object $button
	 */
	public function saveDraft($button) {
		$values = $button->getForm()->getValues();
		$this->manageSavingOld($values, "Článek byl úspěšně uložen.", "show", 0);
	}

	/**
	 * Save and continue editing
	 * @param  object $button
	 */
	public function saveAndContinue($button) {
		$values = $button->getForm()->getValues();
		$this->manageSavingOld($values, "Článek byl úspěšně uložen.", "edit#tinymce", 0);
	}

	/**
	 * Edit already published article
	 * @param  object $button
	 */
	public function saveArticle($button) {
		$values = $button->getForm()->getValues();
		$this->manageSavingOld($values, "Článek byl úspěšně uložen.", "show", 0);
	}

	/**
	 * Form for creating new article
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentAddArticleForm() {
		$form = new Form;
		$form->addText("title", "Název článku", 50)
			->setRequired("Vložte prosím název článku.")
			->addRule(Form::MAX_LENGTH, "Název článku je příliš dlouhý. Maximální délka je %d znaků.", 50);

		$form->addTextArea("perex", "Perex")
			->setRequired("Vložte prosím perex.")
			->addRule(Form::MAX_LENGTH, "Perex je příliš dlouhý. Maximální délka je %d znaků.", 500)
			->setAttribute("cols", self::PEREX_TEXTAREA_COLS)
			->setAttribute("rows", self::PEREX_TEXTAREA_ROWS);

		$form->addCheckBox("social", "Zobrazit u tohoto článku tlačítka sociálních sítí");

		$form->addCheckBox("comments", "Povolit u tohoto článku přidávání komentářů")
			->setValue("true");

		$form->addSelect("poll", "Anketa", $this->polls->findAllPollsAsArray())
			->setPrompt("Vyberte anketu");

		$form->addTextArea("text")
			->setRequired(false)
			->addRule(Form::MAX_LENGTH, "Text je příliš dlouhý. Maximální délka je 20 000 znaků.", 20000)
			->setAttribute("cols", self::TEXT_TEXTAREA_COLS)
			->setAttribute("rows", self::TEXT_TEXTAREA_ROWS)
			->setAttribute("class", "tinymce");

		$this->formUtils->recoverData($form);
		$this->formUtils->manageUidToken($form, $this->addTokenName);

		$form->addSubmit("save_n_publish", "Hned zveřejnit")
			->onClick[] = [$this, "saveAndPublishNewArticle"];

		$form->addSubmit("save_on_time", "Určit datum vydání")
			->onClick[] = [$this, "saveNewArticleWithTime"];

		$form->addSubmit("save_draft", "Uložit do rozepsaných")
			->onClick[] = [$this, "saveNewAsDraft"];

		$form->addSubmit("save_n_continue", "Uložit a psát dál")
			->onClick[] = [$this, "saveNewAndContinue"];

		$this->formUtils->addFormProtection($form);
		$this->formUtils->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process creating an article
	 * @param  array  $values         array of values from the form
	 * @param  number $draft          0 if publish, 1 if keep as draft
	 * @param  string $messageSuccess message for situation if creating is sucessful
	 * @param  string $target         target page after process is successfully finished
	 */
	private function manageSavingNew($values, $draft, $messageSuccess, $target) {
		$uid = $values->uid;
		$t_name = $this->addTokenName;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			// check duplicity of title
			if ($this->articles->isNewArticleTitleDuplicate($values->title)) {
				$this->flashMessages->flashMessageError($this->badTitleError);
				$this->formUtils->recoverInputs($values);
			// check duplicity of url
			} elseif ($this->articles->isNewArticleUrlDuplicate($values->title)) {
				$this->flashMessages->flashMessageError($this->badUrlError);
				$this->formUtils->recoverInputs($values);
			// success, save and redirect
			} else {
				$id = $this->articles->insert($values, $this->getUser()->id, $draft);
				$this->flashMessages->flashMessageSuccess($messageSuccess);
				$this->redirect($target, $id);
			}
		// problem with session
		} else {
			$art = $this->articles->findLast();
			// action was performed, session is gone, but the data fits
			if ($art && $art->title == $values->title   && $art->perex == $values->perex
					 && $art->social == $values->social && $art->comments == $values->comments
					 && $art->id_poll == $values->poll  && $art->text == $values->text) {
				$this->flashMessages->flashMessageSuccess($messageSuccess);
				$this->redirect($target, $art->id_article);
			}
			// repeat warnings about duplicities
			elseif ($this->articles->isNewArticleTitleDuplicate($values->title)) {
				$this->flashMessages->flashMessageError($this->badTitleError);
				$this->formUtils->recoverInputs($values);
			} elseif ($this->articles->isNewArticleUrlDuplicate($values->title)) {
				$this->flashMessages->flashMessageError($this->badUrlError);
				$this->formUtils->recoverInputs($values);
			// action was performed, session is gone and something is wrong
			} else {
				$this->flashMessages->savingErrorFlashMessage();
				$this->formUtils->recoverInputs($values);
			}
		}
	}

	/**
	 * Create and publish new article
	 * @param  object $button
	 */
	public function saveAndPublishNewArticle($button) {
		$values = $button->getForm()->getValues();
		$this->manageSavingNew($values, 0, "Článek byl úspěšně vytvořen a zveřejněn.", "show");
	}

	/**
	 * Save article as draft and continue for selecting publish date
	 * @param  object $button
	 */
	public function saveNewArticleWithTime($button) {
		$values = $button->getForm()->getValues();
		$this->manageSavingNew($values, 1, "Článek byl úspěšně uložen jako rozepsaný", "publish");
	}

	/**
	 * Save new article as draft
	 * @param  object $button
	 */
	public function saveNewAsDraft($button) {
		$values = $button->getForm()->getValues();
		$this->manageSavingNew($values, 1, "Článek byl úspěšně uložen jako rozepsaný.", "show");
	}

	/**
	 * Save new article as draft and continue editing
	 * @param  object $button
	 */
	public function saveNewAndContinue($button) {
		$values = $button->getForm()->getValues();
		$this->manageSavingNew($values, 1, "Článek byl úspěšně uložen jako rozepsaný.", "edit#tinymce");
	}

}
