<?php

namespace App\Model;

use Nette;
use Nette\Utils\Strings;

class Article extends Nette\Object {

	const ID_COLUMN = "id_article"; 
	const TITLE_COLUMN = "title";
	const DATE_COLUMN = "date";
	const TEXT_COLUMN = "text";
	const PEREX_COLUMN = "anotation";
	const DRAFT_COLUMN = "draft";
	const ID_EDITOR_COLUMN = "id_editor";
	const COMMENTS_COLUMN = "comments";
	const SOCIAL_COLUMN = "social";
	const VIEWS_COLUMN = "views";
	const URL_COLUMN = "url";
	const ID_POLL_COLUMN = "id_poll";

	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * Find article by id
	 * @param  number $id id of article
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findById($id) {
		return $this->database->table("article")->get($id);
	}

	/**
	 * Find article by title
	 * @param  string $title title of article
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findByTitle($title) {
		return $this->database->table("article")->where(self::TITLE_COLUMN, $title)->fetch();
	}

	/**
	 * Find all articles for poll
	 * @param  number $id id of poll
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllArticlesByPollId($id) {
		return $this->database->table("article")->where(self::ID_POLL_COLUMN, $id)->order(self::DATE_COLUMN);
	}

	/**
	 * FInd all draft articles
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllDrafts() {
		return $this->database->table("article")->where(self::DRAFT_COLUMN, 1)->order(self::DATE_COLUMN." DESC");
	}

	/**
	 * Find last article, the one with the highest id
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findLast() {
		return $this->database->table("article")->order(self::ID_COLUMN." DESC")->fetch();
	}

	/**
	 * Find all non draft articles
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllNonDraftsArticles() {
		return $this->database->table("article")->where(self::DRAFT_COLUMN, 0)->order(self::DATE_COLUMN." DESC");
	}

	/**
	 * Find all non draft articles within a time period for month categories
	 * @param  number $start start time of period
	 * @param  number $end   end time of period
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllNonDraftArticlesByTimeInterval($start, $end) {
		return $this->database->table("article")->order(self::DATE_COLUMN)
				->where(self::DATE_COLUMN." >= ?", $start)->where(self::DATE_COLUMN." <= ?", $end)->where(self::DRAFT_COLUMN, 0);
	}

	/**
	 * Check if there is any article having duplicate title
	 * @param  string  $title new title for an article
	 * @param  number  $id    id of an article
	 * @return boolean        true if there is a duplicate title, false otherwise
	 */
	public function isOldArticleTitleDuplicate($title, $id) {
		return $this->database->table("article")->where(self::TITLE_COLUMN, $title)
				->where(self::ID_COLUMN." != ?", $id)->count() > 0;
	}

	/**
	 * Check if there is any article having duplicate url
	 * @param  string  $title new title for an article
	 * @param  number  $id    id of an article
	 * @return boolean        true if there is a duplicate url, false otherwise
	 */
	public function isOldArticleUrlDuplicate($title, $id) {
		return $this->database->table("article")->where(self::URL_COLUMN, Strings::webalize($title))
				->where(self::ID_COLUMN." != ?", $id)->count() > 0;
	}

	/**
	 * Check if there is any article having duplicate title
	 * @param  string  $title title for a new article
	 * @return boolean        true if there is a duplicate title, false otherwise
	 */
	public function isNewArticleTitleDuplicate($title) {
		return $this->database->table("article")->where(self::TITLE_COLUMN, $title)->count() > 0;
	}

	/**
	 * Check if there is any article having duplicate url
	 * @param  string  $title title for a new article
	 * @return boolean        true if there is a duplicate url, false otherwise
	 */
	public function isNewArticleUrlDuplicate($title) {
		return $this->database->table("article")->where(self::URL_COLUMN, Strings::webalize($title))->count() > 0;
	}

	/**
	 * Create new article and possible publish it
	 * @param  array $values  array of values
	 * @param  number $editor id of logged user, saved as author of article
	 * @param  number $draft  0 if publish, 1 if save as draft
	 * @return number         id of newly created article
	 */
	public function insert($values, $editor, $draft) {
		$social = ($values->social) ? 1 : 0;
		$comments = ($values->comments) ? 1 : 0;

		return $this->database->table("article")->insert(array(
			self::TITLE_COLUMN => $values->title, self::DATE_COLUMN => time(), self::TEXT_COLUMN => $values->text,
			self::PEREX_COLUMN => $values->anotation, self::DRAFT_COLUMN => $draft, self::ID_EDITOR_COLUMN => $editor,
			self::SOCIAL_COLUMN => $social, self::COMMENTS_COLUMN => $comments, self::ID_POLL_COLUMN => $values->poll,
			self::URL_COLUMN => Strings::webalize($values->title)
		));
	}

	/**
	 * Edit article
	 * @param  array $values array of new values
	 * @param  number $id    id of article
	 */
	public function edit($values, $id) {
		$social = ($values->social) ? 1 : 0;
		$comments = ($values->comments) ? 1 : 0;

		$this->database->table("article")->where(self::ID_COLUMN, $id)->update(array(
			self::TITLE_COLUMN => $values->title, self::TEXT_COLUMN => $values->text,
			self::PEREX_COLUMN => $values->anotation, self::SOCIAL_COLUMN => $social,
			self::COMMENTS_COLUMN => $comments, self::ID_POLL_COLUMN => $values->poll
		));
	}

	/**
	 * Delete article, its comments and assignment of tags
	 * @param  number $id id of article
	 */
	public function delete($id) {
		$this->database->table("article_tag")->where(Tag::AT_ARTICLE_COLUMN, $id)->delete();
		$this->database->table("comment")->where(Comment::ID_ARTICLE_COLUMN, $id)->delete();
		$this->database->table("article")->where(self::ID_COLUMN, $id)->delete();
	}

	/**
	 * Delete poll from article
	 * @param  number $article id of article
	 * @return number          number of updated rows, idealy 1, when there is an error then 0
	 */
	public function deletePollFromArticle($article) {
		return $this->database->table("article")->where(self::ID_COLUMN, $article)->update(array(
			self::ID_POLL_COLUMN => NULL
		));
	}

	/**
	 * Publish draft article
	 * @param  number $id   id of article
	 * @param  number $time time of publishing, possible to set into the future
	 */
	public function publish($id, $time) {
		$title = $this->database->table("article")->where(self::ID_COLUMN, $id)->fetch()->title;

		$this->database->table("article")->where(self::ID_COLUMN, $id)->update(array(
			self::URL_COLUMN => Strings::webalize($title),
			self::DRAFT_COLUMN => 0, self::DATE_COLUMN => $time
		));
	}
}
