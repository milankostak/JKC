<?php

namespace App\Presenters;

use App\Model\Blog;
use Nette\Application\UI\Form;

class SettingsPresenter extends AdminPresenter {

	const BOX_TEXTAREA_COLS = 80;
	const BOX_TEXTAREA_ROWS = 15;

	private $blog, $blogs;

	private $generalSettingsTokenName = "general_settings";
	private $editTopBoxTokenName = "edit_top_box";
	private $editBottomBoxTokenName = "edit_bottom_box";

	public function inject(Blog $blog) {
		$this->blogs= $blog;
	}

	protected function startup() {
		parent::startup();
		$this->blog = $this->blogs->getBlogInfo();
		$this->template->big_title = $this->blog->name;
	}

	/**
	 * Custom bootstrap styles for editing top and bottom boxes
	 * @param  Form $form form for applying styles
	 */
	private function makeBootstrapFormForSettings($form) {
		$renderer = $form->getRenderer();
		$renderer->wrappers['controls']['container'] = null;
		$renderer->wrappers['pair']['container'] = 'div class="row form-group"';
		$renderer->wrappers['label']['container'] = null;
		$renderer->wrappers['control']['container'] = 'div class="col-xs-12"';
		$renderer->wrappers['control']['.submit'] = 'btn btn-primary';
	}

	/**
	 * Render default page, show only menu
	 */
	public function renderDefault() { }

	/**
	 * Render page with general settings
	 */
	public function renderGeneral() { }

	/**
	 * Form for changing general settings on Settings:general
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentEditSettingsForm() {
		$bl = $this->blog;
		$form = new Form;

		$form->addText("name", "Název blogu", 50)
			->setRequired("Vložte prosím název blogu.")
			->addRule(Form::MAX_LENGTH, "Název blogu je příliš dlouhý. Maximální délka je %d znaků.", 50)
			->setValue($bl->name);

		$form->addText("sub_name", "Podtitul blogu", 70)
			->setRequired("Vložte prosím podtitul blogu.")
			->addRule(Form::MAX_LENGTH, "Podtitul blogu je příliš dlouhý. Maximální délka je %d znaků.", 70)
			->setValue($bl->sub_name);

		$form->addText("posts_per_page", "Počet článků / 1 stránka", 7)
			->setType("number")
			->addRule(Form::INTEGER, "Počet článků na 1 stránku musí být číslo.")
			->addRule(Form::RANGE, "Počet článků na 1 stránku musí být v rozsahu od %d do %d.", [2, 99])
			->setRequired("Vložte prosím počet článků na 1 stránku.")
			->setValue($bl->posts_per_page);

		$form->addText("number_last_posts", "Počet posledních článků", 7)
			->setType("number")
			->addRule(Form::INTEGER, "Počet článků na 1 stránku musí být číslo.")
			->addRule(Form::RANGE, "Počet článků na 1 stránku musí být v rozsahu od %d do %d.", [0, 50])
			->setRequired("Vložte prosím počet článků na 1 stránku.")
			->setValue($bl->number_last_posts);

		$form->addText("number_rss_articles", "Počet posledních článků v RSS", 7)
			->setType("number")
			->addRule(Form::INTEGER, "Počet článků v RSS musí být číslo.")
			->addRule(Form::RANGE, "Počet článků v RSS musí být v rozsahu od %d do %d.", [3, 20])
			->setRequired("Vložte prosím počet článků v RSS.")
			->setValue($bl->number_rss_articles);

		$form->addText("number_rss_comments", "Počet posledních komentářů v RSS", 7)
			->setType("number")
			->addRule(Form::INTEGER, "Počet komentářů v RSS musí být číslo.")
			->addRule(Form::RANGE, "Počet komentářů v RSS musí být v rozsahu od %d do %d.", [3, 20])
			->setRequired("Vložte prosím počet komentářů v RSS.")
			->setValue($bl->number_rss_comments);

		$form->addText("ga", "Google Analytics", 17)
			->setRequired(false)
			->addRule(Form::PATTERN, "Neplatný Google Analytics kód.", "^(UA-\d{3,10}-\d{1,4}|)$")
			->setValue($bl->ga);

		$this->recoverData($form);
		$this->manageUidToken($form, $this->generalSettingsTokenName);

		$form->addSubmit("save", "Uložit nastavení");
		$form->onSuccess[] = [$this, "saveSettings"];

		$this->addFormProtection($form);
		$this->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process saving general settings
	 * @param  Form  $form
	 * @param  array $values array of values
	 */
	public function saveSettings(Form $form, $values) {
		$uid = $values->uid;
		$t_name = $this->generalSettingsTokenName;
		$bl = $this->blog;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			$this->blogs->editBlogInfo($values);
		// action was performed, session is gone, but the data fits
		} elseif ($values->name != $bl->name
			|| $values->posts_per_page != $bl->posts_per_page
			|| $values->number_last_posts != $bl->number_last_posts
			|| $values->number_rss_articles != $bl->number_rss_articles
			|| $values->number_rss_comments != $bl->number_rss_comments
			|| $values->ga != $bl->ga) {
				$this->flashMessages->savingErrorFlashMessage();
				$this->recoverInputs($values);
		}
		$this->flashMessages->flashMessageSuccess("Změny byly uloženy.");
		$this->redirect("this");
	}

	/**
	 * Render page for top box changing
	 */
	public function renderTopBox() { }

	/**
	 * Form for changing top box
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentEditTopBoxForm() {
		$form = new Form;

		$form->addTextArea("top_box")
			->setRequired(false)
			->addRule(Form::MAX_LENGTH, "Text je příliš dlouhý. Maximální délka je %d znaků.", 1000)
			->setAttribute("cols", self::BOX_TEXTAREA_COLS)
			->setAttribute("rows", self::BOX_TEXTAREA_ROWS)
			->setAttribute("class", "tinymce")
			->setValue($this->blog->top_box);

		$this->recoverData($form);
		$this->manageUidToken($form, $this->editTopBoxTokenName);

		$form->addSubmit("save", "Uložit");
		$form->onSuccess[] = [$this, "saveTopBox"];

		$this->addFormProtection($form);
		$this->makeBootstrapFormForSettings($form);
		return $form;
	}

	/**
	 * Process saving general settings
	 * @param  Form  $form
	 * @param  array $values array of values
	 */
	public function saveTopBox(Form $form, $values) {
		$uid = $values->uid;
		$t_name = $this->editTopBoxTokenName;
		$top_box = $values->top_box;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			$this->blogs->editTopBox($top_box);
			$this->flashMessages->flashMessageSuccess("Volitelný horní box byl úspěšně uložen.");
			$this->redirect("this");
		} else {
			// action was performed, session is gone, but the data fits
			if ($this->blog->top_box == $top_box) {
				$this->flashMessages->flashMessageSuccess("Volitelný horní box byl úspěšně uložen.");
				$this->redirect("this");
			// action was performed, session is gone and something is wrong
			} else {
				$this->flashMessages->savingErrorFlashMessage();
				$this->recoverInputs($values);
			}
		}
	}

	/**
	 * Render page for bottom box changing
	 */
	public function renderBottomBox() { }

	/**
	 * Form for changing bottom box
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentEditBottomBoxForm() {
		$form = new Form;

		$form->addTextArea("bottom_box")
			->setRequired(false)
			->addRule(Form::MAX_LENGTH, "Text je příliš dlouhý. Maximální délka je %d znaků.", 1000)
			->setAttribute("cols", self::BOX_TEXTAREA_COLS)
			->setAttribute("rows", self::BOX_TEXTAREA_ROWS)
			->setAttribute("class", "tinymce")
			->setValue($this->blog->bottom_box);

		$this->recoverData($form);
		$this->manageUidToken($form, $this->editBottomBoxTokenName);

		$form->addSubmit("save", "Uložit");
		$form->onSuccess[] = [$this, "saveBottomBox"];

		$this->addFormProtection($form);
		$this->makeBootstrapFormForSettings($form);
		return $form;
	}

	/**
	 * Process saving general settings
	 * @param  Form  $form
	 * @param  array $values array of values
	 */
	public function saveBottomBox(Form $form, $values) {
		$uid = $values->uid;
		$t_name = $this->editBottomBoxTokenName;
		$bottom_box = $values->bottom_box;

		// session is ok
		if ($this->getSession($t_name)[$uid] == $uid) {
			unset($this->getSession($t_name)[$uid]);
			$this->blogs->editBottomBox($bottom_box);
			$this->flashMessages->flashMessageSuccess("Volitelný dolní box byl úspěšně uložen.");
			$this->redirect("this");
		} else {
			// action was performed, session is gone, but the data fits
			if ($this->blog->bottom_box == $bottom_box) {
				$this->flashMessages->flashMessageSuccess("Volitelný dolní box byl úspěšně uložen.");
				$this->redirect("this");
			// action was performed, session is gone and something is wrong
			} else {
				$this->flashMessages->savingErrorFlashMessage();
				$this->recoverInputs($values);
			}
		}
	}

}
