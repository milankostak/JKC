<?php

namespace App\Model;

use Nette;

class Comment extends Nette\LegacyObject {

	const COMMENT_NOTFOUND = 0;
	const COMMENT_DELETED = 1;
	const COMMENT_PUBLISHED = 2;
	const COMMENT_UNPUBLISHED = 3;
	const COMMENT_PUBLISH_ERROR = 4;

	const ID_COLUMN = "id_comment";
	const TEXT_COLUMN = "text";
	const DATE_COLUMN = "date";
	const AUTHOR_COLUMN = "author";
	const MAIL_COLUMN = "mail";
	const SUBJECT_COLUMN = "subject";
	const EDITOR_COLUMN = "editor";
	const DELETED_COLUMN = "deleted";
	const ID_ARTICLE_COLUMN = "id_article";

	/** @var Nette\Database\Context */
	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * Find all comments for a post
	 * @param  string $url url of a post
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllPostComments($url) {
		return $this->database->table("comment")->where("article.".Article::URL_COLUMN, $url)
				->where(self::DELETED_COLUMN, 0)->order(self::DATE_COLUMN);
	}

	/**
	 * Find the last comment, the one with the highest id
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findLast() {
		return $this->database->table("comment")->order(self::ID_COLUMN." DESC")->fetch();
	}

	/** 
	 * Find all comments for the article, including deleted
	 * @param  number $id id of the article
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllCommentsByArticleId($id) {
		return $this->database->table("comment")->order(self::ID_COLUMN." DESC")->where(self::ID_ARTICLE_COLUMN, $id);
	}

	/** 
	 * Find all comments of an article, exclude deleted articles
	 * @return Nette\Database\Table\Selection
	 */
	public function findNonDeletedCommentsByArticleId($id) {
		return $this->findAllCommentsByArticleId($id)->where(self::DELETED_COLUMN, 0);
	}

	/**
	 * Insert a new comment
	 * @param  array $values  array of values
	 * @param  number $editor 0 if comment is not created by any editor, 1 otherwise
	 * @param  number $id     id of the article
	 */
	public function insert($values, $editor, $id) {
		$mail = ($values->mail == "") ? null : $values->mail;
		$subject = ($values->subject == "") ? null : $values->subject;

		$this->database->table("comment")->insert(array(
			self::TEXT_COLUMN => $values->text, self::DATE_COLUMN => time(), self::AUTHOR_COLUMN => $values->author,
			self::MAIL_COLUMN => $mail, self::SUBJECT_COLUMN => $subject, self::EDITOR_COLUMN => $editor,
			self::ID_ARTICLE_COLUMN => $id
		));
	}

	/**
	 * Delete the comment
	 * @param  number $id id of the comment
	 * @return array      array with information for later redirection
	 */
	public function deleteById($id) {
		$comment = $this->database->table("comment")->get($id);
		if ($comment)  {
			$id = $comment->id_article;
			$comment->delete();
			return array(self::COMMENT_DELETED, $id);
		} else {
			return array(self::COMMENT_NOTFOUND, null);
		}
	}

	/**
	 * Unpublish the comment, make it not visible
	 * @param  number $id id of the comment
	 * @return array      array with information for later redirection
	 */
	public function unpublishById($id) {
		$comment = $this->database->table("comment")->get($id);
		if ($comment)  {
			if ($comment->deleted == 1) {
				return array(self::COMMENT_PUBLISH_ERROR, $comment->id_article);
			} else {
				$this->database->table("comment")->where(self::ID_COLUMN, $id)->update(array(self::DELETED_COLUMN => 1));
				return array(self::COMMENT_UNPUBLISHED, $comment->id_article);
			}
		} else {
			return array(self::COMMENT_NOTFOUND, null);
		}
	}

	/**
	 * Publish the comment, make it visible again
	 * @param  number $id id of a comment
	 * @return array      array with information for later redirection
	 */
	public function publishById($id) {
		$comment = $this->database->table("comment")->get($id);
		if ($comment)  {
			if ($comment->deleted == 0) {
				return array(self::COMMENT_PUBLISH_ERROR, $comment->id_article);
			} else {
				$this->database->table("comment")->where(self::ID_COLUMN, $id)->update(array(self::DELETED_COLUMN => 0));
				return array(self::COMMENT_PUBLISHED, $comment->id_article);
			} 
		} else {
			return array(self::COMMENT_NOTFOUND, null);
		}
	}

	/**
	 * Find last comments for RSS
	 * @param  number $limit number of last comments
	 * @return Nette\Database\Table\Selection
	 */
	public function findLastComments($limit) {
		return $this->database->table("comment")->order(self::DATE_COLUMN." DESC")->limit($limit)
				->where("article.".Article::DRAFT_COLUMN, 0)->where("article.".Article::DATE_COLUMN." < ?", time())
				->where(Article::COMMENTS_COLUMN, 1)->where(self::DELETED_COLUMN, 0);
	}

}
