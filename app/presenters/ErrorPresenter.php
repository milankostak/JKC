<?php

namespace App\Presenters;

use App\Model\Post, \App\Model\Tag, \App\Model\Blog;
use Nette\Application\BadRequestException,
	Nette\Diagnostics\Debugger;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Application\UI\Form;

class ErrorPresenter extends BasePresenter {

	private $blog, $posts, $tags;

	public function inject(Post $posts, Tag $tags,Blog $blog) {
		$this->blog = $blog;
		$this->posts = $posts;
		$this->tags = $tags;
	}

	protected function startup() {
		parent::startup();
		$this->blog = $this->blog->getBlogInfo();
		$this->template->lastPosts = $this->posts->findLastPosts($this->blog->number_last_posts);
		$this->template->tags = $this->tags->findAll();
		$this->template->big_title = $this->blog->name;
		$this->template->ga = $this->blog->ga;
		$this->template->top_box = $this->blog->top_box;
		$this->template->bottom_box = $this->blog->bottom_box;
	}

	public function renderDefault($exception) {
		if ($exception instanceof BadRequestException) {
			$code = $exception->getCode();
			$this->setView(in_array($code, [403, 404, 500]) ? $code : "4xx");
			$this->template->code = $code;
			Debugger::log($exception, Debugger::ERROR);
		} else {
			$this->setView("500");
			Debugger::log($exception, Debugger::ERROR);
		}
		$this->sendEmail();
	}

	private function sendEmail() {
		$mail = new Message;
		$mail->setFrom("error@milan-kostak.cz")
		    ->addTo("milankostak@gmail.com")
			->setSubject("Cestopisy: Server error")
			->setBody("Server error occured on web 'Cestopisy'. Check log as soon as possible.");

		$mailer = new SendmailMailer;
		$mailer->send($mail);
	}
}
