<?php

namespace App\Presenters;

use App\Model\Post, App\Model\Comment, App\Model\Blog;

class RssPresenter extends BasePresenter {

	private $posts, $blog, $comments;

	public function inject(Post $posts, Comment $comments, Blog $blog) {
		$this->posts = $posts;
		$this->comments = $comments;
		$this->blog = $blog;
	}

	protected function startup() {
		parent::startup();
		$this->template->blog = $this->blog = $this->blog->getBlogInfo();
	}

	/**
	 * Redirect default to articles
	 */
	public function actionDefault() {
		$this->redirect("articles");
	}

	/**
	 * RSS feed with newest articles
	 */
	public function renderArticles() {
		$this->template->posts = $this->posts->findLastPosts($this->blog->number_rss_articles);
	}

	/**
	 * RSS feed with newest comments
	 */
	public function renderComments() {
		$this->template->comments = $this->comments->findLastComments($this->blog->number_rss_comments);
	}
}
