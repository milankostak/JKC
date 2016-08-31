<?php

namespace App\Presenters;

use App\Model\Post, App\Model\Comment, App\Model\Poll, App\Model\Tag, App\Model\Blog, App\Model\Editor;
use Nette\Application\UI\Form;
use Nette\Utils\Paginator;

class PostPresenter extends BasePresenter {

	private $posts, $comments, $polls, $tags, $blog;

	private $addCommentTokenName = "comment_adding";

	public function inject(Post $posts, Comment $comments, Poll $polls, Tag $tags, Blog $blog) {
		$this->posts = $posts;
		$this->comments = $comments;
		$this->polls = $polls;
		$this->tags = $tags;
		$this->blog = $blog;
	}

	protected function startup() {
		parent::startup();
		$this->blog = $this->blog->getBlogInfo();
	}

	/**
	 * Check if post exists
	 * @param  string $url url of a post
	 * @return Nette\Database\Table\ActiveRow object with post data if post is found, redirect otherwise
	 */
	private function doesPostExists($url) {
		$post = $this->posts->findById($url);
		if (!$post) {
			$this->flashMessage("Článek '$url' neexistuje.", "error");
			$this->redirect("default");
		} else {
			return $post;
		}
	}	

	private function manageSideBar() {
		$this->template->tags = $this->tags->findAll();
		$this->template->lastPosts = $this->posts->findLastPosts($this->blog->number_last_posts);
		$this->template->big_title = $this->blog->name;
		$this->template->small_title = $this->blog->sub_name;
		$this->template->top_box = $this->blog->top_box;
		$this->template->bottom_box = $this->blog->bottom_box;
		$this->template->ga = $this->blog->ga;
	}

	/**
	 * Archive content for archive and map pages
	 */
	private function prepareArchiveMenu() {
		$art = $this->posts->findBasicAll();
		$all = [];
		foreach ($art as $a) {
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
	 * Manage poll, find out if already voted, set options and sum of votes
	 * @param  number $id_poll id of a poll
	 * @param  number $voted   0 if there is need to find from cookies if already voted, 1 if already voted
	 */
	private function managePoll($id_poll, $voted = 0) {
		$this->template->sum_votes = $this->polls->findPollSumVotes($id_poll);
		$this->template->options = $this->polls->findAllPollOptions($id_poll);

		if ($voted == 0) {
			$this->template->voted = $this->context->getService("httpRequest")->getCookie("poll_".$id_poll);
		} else {
			$this->template->voted = 1;
		}
	}

	/**
	 * Manage increase of page views
	 * @param  string $url   url of a post
	 * @param  number $views number of current views
	 * @return number        new number of views
	 */
	private function manageViewsCount($url, $views) {
		if (!isset($_COOKIE["milan"])) {
			if (!$this->getSession("views")->$url) {
				$this->getSession("views")->$url = 1;
				$this->posts->increaseViews($url);
				return ++$views;
			}
		}
		return $views;
	}

	/**
	 * Default page with list of paginated posts
	 * @param  number $page number of a page
	 */
	public function renderDefault($page) {
		$paginator = new Paginator;
		$paginator->setItemCount($this->posts->getCount());
		$paginator->setItemsPerPage($this->blog->posts_per_page); 
		$paginator->setPage($page);

		$this->template->posts = $this->posts->findAllByLimitAndOffset($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator = $paginator;
		$this->manageSideBar();
	}

	/**
	 * Render page with post detail
	 * @param  string $url url of a post
	 */
	public function renderPost($url) {
		$post = $this->posts->findByIdWithCommentsCountAndTags($url);
		if (!$post) {
			$this->flashMessage("Článek '$url' neexistuje.", "error");
			$this->redirect("default");
		} else {
			$this->redrawControl("polll");
			$post->views = $this->manageViewsCount($url, $post->views);
			$this->template->post = $post;
			$poll = $this->posts->findPostPoll($url);
			if ($poll) {
				$this->managePoll($poll->id_poll);
			}
			$this->template->poll = $poll;
			$this->manageSideBar();
		}
	}

	/**
	 * Manage vote in poll
	 * @param  number $id   id of an option clicked
	 * @param  number $poll id of a poll
	 * @param  string $url  url of a post
	 */
	public function actionVote($id, $poll, $url) {
		$voted = $this->context->getService("httpRequest")->getCookie("poll_".$poll);
		$ajax = $this->isAjax();

		if ($voted == 1 || $this->polls->findOptionById($id)->id_poll != $poll) {
			$ms = ($voted == 1) ? "V této anketě jste již hlasoval(a)." : "Tato odpověď nepatří k této anketě.";
			if (!$ajax) {
				$this->flashMessage($ms, "error");
				$this->redirect("post", $url);
			} else {
				$this->managePoll($poll, 1);
				$this->template->ajaxMessage = ["status" => "ajaxerror", "message" => $ms];
				$this->redrawControl("polll");
			}
		} else {
			$this->context->getService("httpResponse")->setCookie("poll_".$poll, "1", "30 days");
			$this->polls->vote($id);
			$ms = "Hlas byl úspěšně započítán.";

			if (!$ajax) {
				$this->flashMessage($ms, "success");
				$this->redirect("post#poll", $url);
			} else {
				$this->managePoll($poll, 1);
				$this->template->ajaxMessage = ["status" => "ajaxsuccess", "message" => $ms];
				$this->redrawControl("polll");
			}
		}

	}

	/**
	 * Render page with comments of a post
	 * @param  string $url url of a post
	 */
	public function renderComments($url) {
		$post = $this->doesPostExists($url);
		if ($post->comments == 0) {
			$this->flashMessage("K tomuto článku nejsou komentáře dostupné.", "error");
			$this->redirect("post", $url);
		} else {
			$this->template->post = $post;
			$this->template->comments = $this->comments->findAllPostComments($url);
			$this->manageSideBar();
		}
	}

	/**
	 * Form for adding comment
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentAddCommentForm() {
		$form = new Form;

		$author = $form->addText("author", "Jméno", 35)
			->setRequired("Vložte prosím své jméno nebo alespoň přezdívku.")
			->addRule(Form::MAX_LENGTH, "Vložené jméno je příliš dlouhé. Maximální délka je %d znaků.", 30);

		if ($this->getUser()->isLoggedIn()) {
			$author->setValue($this->getUser()->identity->data[Editor::NAME_COLUMN]);
		}

		$form->addText("subject", "Předmět", 35)
			->setRequired(false)
			->addRule(Form::MAX_LENGTH, "Vložený předmět je příliš dlouhý. Maximální délka je %d znaků.", 30);

		$form->addText("mail", "E-mail", 35)
			->setRequired(false)
			->addRule(Form::MAX_LENGTH, "Vložený e-mail je příliš dlouhý. Maximální délka je %d znaků.", 40)
			->addRule(Form::PATTERN, "Neplatný e-mail", "^([_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})|)$");

		$form->addTextArea("text", "Komentář")
			->setRequired("Vložte prosím text Vašeho komentáře.")
			->addRule(Form::MAX_LENGTH, "Text je příliš dlouhý. Maximální délka je %d znaků.", 1000)
			->setAttribute("rows", 10);

		$this->recoverData($form);
		$this->manageUidToken($form, $this->addCommentTokenName);

		$form->addSubmit("save", "Uložit komentář");
		$form->onSuccess[] = [$this, "saveComment"];

		$this->addFormProtection($form);
		$this->makeBootstrapForm($form);
		return $form;
	}

	/**
	 * Process saving of a comment
	 * @param  Form  $form
	 * @param  array $values array of values from the form
	 */
	public function saveComment(Form $form, $values) {
		$url = $this->getParameter("url");
		$post = $this->doesPostExists($url);
		$uid = $values->uid;
		$t_name = $this->addCommentTokenName;

		// comments are not allowed
		if ($post->comments == 0) {
			$this->flashMessage("K tomuto článku nelze vkládat komentáře.", "error");
			$this->redirect("post", $url);
		} else {
			// session is ok
			if ($this->getSession($t_name)[$uid] == $uid) {
				unset($this->getSession($t_name)[$uid]);
				$editor = ($this->user->isLoggedIn()) ? 1 : 0;
				$this->comments->insert($values, $editor, $post->id_article);
				$this->flashMessage("Komentář byl úspěšně uložen.", "success");
				$this->redirect("comments", $url);
			// problem with session
			} else {
				$comment = $this->comments->findLast();
				// action was performed, session is gone, but the data fits
				if ($comment && $comment->text == $values->text && $comment->date > time()-15
					&& $comment->author == $values->author && $comment->mail == $values->mail
					&& $comment->subject == $values->subject && $comment->deleted == 0) {
					$this->flashMessage("Komentář byl úspěšně uložen.", "success");
					$this->redirect("comments", $url);
				// action was performed, session is gone and something is wrong
				} else {
					$this->savingErrorFlashMessage();
					$this->recoverInputs($values);
				}
			}
		}
	}

	/**
	 * Render page for sorting post by tag, enable paginating
	 * @param  string $url  url of a tag
	 * @param  number $page page-number
	 */
	public function renderTag($url, $page) {
		$tag = $this->tags->findByUrl($url);
		if (!$tag) {
			$this->flashMessage("Štítek s názvem '$url' neexistuje.", "error");
			$this->redirect("default");
		} else {
			$paginator = new Paginator;
			$paginator->setItemCount($this->posts->getArticleCountPerTag($url));
			$paginator->setItemsPerPage($this->blog->posts_per_page);
			$paginator->setPage($page);

			$this->template->posts = $this->posts->findAllPostsByTagName($url, $paginator->getLength(), $paginator->getOffset());
			$this->template->paginator = $paginator;
			$this->template->this_tag = $tag;
			$this->manageSideBar();
		}
	}

	/**
	 * Render page with archive
	 * @param  number $year
	 * @param  number $month
	 */
	public function renderArchive($year, $month) {
		$this->manageSideBar();
		$isYear = isset($year);
		$isMonth = isset($month);
		if ($isYear) $this->template->actual_year = $year;
		if ($isMonth) $this->template->actual_month = $month;

		if ($isYear && $isMonth) {
			$start_date = $year."-".$month."-01";
			$start_time = $start_date." 00:00:00";

			$max_day = date("t", strtotime($start_date));
			$end_time = $year."-".$month."-".$max_day." 23:59:59";

			$start_time = new \DateTime($start_time);
			$end_time = new \DateTime($end_time);

			$this->template->posts = $this->posts->findAllPostsByTimeInterval($start_time->getTimestamp(), $end_time->getTimestamp());
		} else {
			$this->prepareArchiveMenu();
		}
	}

	/**
	 * Render page of map of the web
	 */
	public function renderMap() {
		$this->manageSideBar();
		$this->template->articles = $this->posts->findBasicAll();
		$this->template->tags = $this->tags->findAll();
		$this->prepareArchiveMenu();
	}
}
