<?php

namespace App\Presenters;

use App\Model\Comment;

class CommentPresenter extends SecuredPresenter {

	private $comments;

	private $commentNotFoundError = "Tento komentář neexistuje.";

	public function inject(Comment $comments) {
		$this->comments = $comments;
	}

	protected function startup() {
		parent::startup();
	}

	/**
	 * Get url parameters for redirection after publishing/deleting of a comment
	 * @param  number $id id of article
	 * @return array      array containing parameters
	 */
	private function getRedirectParams($id) {
		$paramsArray["id"] = $id;
		if ($this->getParameter("deleted") == "1") {
			$paramsArray["deleted"] = "1";
		}
		return $paramsArray;
	}

	/**
	 * Delete comment
	 * @param  number $id id of comment
	 */
	public function actionDelete($id) {
		$comm = $this->comments->deleteById($id);
		if ($comm[0] == Comment::COMMENT_DELETED) {
			$this->flashMessages->flashMessageSuccess("Komentář byl úspěšně smazán.");
			$this->redirect("Article:comments", $this->getRedirectParams($comm[1]));
		} else {
			$this->flashMessages->flashMessageError($this->commentNotFoundError);
			$this->redirect("Article:default");
		}
	}

	/**
	 * Unpublish comment
	 * @param  number $id id of comment
	 */
	public function actionUnpublish($id) {
		$comm = $this->comments->unpublishById($id);
		if ($comm[0] == Comment::COMMENT_NOTFOUND) {
			$this->flashMessages->flashMessageError($this->commentNotFoundError);
			$this->redirect("Article:default");
		} else if ($comm[0] == Comment::COMMENT_UNPUBLISHED) {
			$this->flashMessages->flashMessageSuccess("Komentář byl úspěšně označen jako neveřejný.");
		} else if ($comm[0] == Comment::COMMENT_PUBLISH_ERROR) {
			$this->flashMessages->flashMessageError("Tento komentář je již označen jako neveřejný.");
		}
		$this->redirect("Article:comments", $this->getRedirectParams($comm[1]));
	}

	/**
	 * Publish comment
	 * @param  number $id id of comment
	 */
	public function actionPublish($id) {
		$comm = $this->comments->publishById($id);
		if ($comm[0] == Comment::COMMENT_NOTFOUND) {
			$this->flashMessages->flashMessageError($this->commentNotFoundError);
			$this->redirect("Article:default");
		} else if ($comm[0] == Comment::COMMENT_PUBLISHED) {
			$this->flashMessages->flashMessageSuccess("Komentář byl úspěšně označen jako veřejný.");
		} else if ($comm[0] == Comment::COMMENT_PUBLISH_ERROR) {
			$this->flashMessages->flashMessageError("Tento komentář je již označen jako veřejný.");
		}
		// always keep page with deleted, because publishing is only possible on page with shown deleted comments
		$this->redirect("Article:comments", ["id" => $comm[1], "deleted" => "1"]);
	}

}
