<?php

namespace App\Presenters;

use App\Model\Rank, App\Model\Blog;

class RankPresenter extends SecuredPresenter {

	private $blog, $rank;

	public function inject(Rank $rank, Blog $blog) {
		$this->rank = $rank;
		$this->blog = $blog;
	}

	protected function startup() {
		parent::startup();
		$this->blog = $this->blog->getBlogInfo();
		$this->template->big_title = $this->blog->name;
	}

	/**
	 * Redirect default to new
	 */
	public function actionDefault() {
		$this->redirect("new");
	}

	/**
	 * Page with list of the newest articles
	 */
	public function renderNew() {
		$this->template->articles = $this->rank->findNewest();
	}

	/**
	 * Page with list of the most visited articles
	 */
	public function renderVisited() {
		$this->template->articles = $this->rank->findMostVisited();
	}

	/**
	 * Page with list of the msot commented articles
	 */
	public function renderCommented() {
		$this->template->articles = $this->rank->findMostCommented();
	}

}
