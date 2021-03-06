<?php

namespace App\Model;

use Nette;
use Nette\Utils\Strings;

class Tag extends Nette\LegacyObject {

	const ID_COLUMN = "id_tag"; 
	const NAME_COLUMN = "name";
	const URL_COLUMN = "url";

	const AT_ARTICLE_COLUMN = "id_article";
	const AT_TAG_COLUMN = "id_tag";

	/** @var Nette\Database\Context */
	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * Find all tags
	 * @return Nette\Database\Table\Selection
	 */
	public function findAll() {
		return $this->database->table("tag")->order(self::NAME_COLUMN);
	}

	/**
	 * Find the tag by id
	 * @param  number $id id of the tag
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findById($id) {
		return $this->database->table("tag")->get($id);
	}

	/**
	 * Find all tags with the number of assigned articles
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllWithCount() {
		return $this->database->table("tag")->group(self::ID_COLUMN)->order(self::NAME_COLUMN)
				->select("tag.*, COUNT(:article_tag.".self::AT_ARTICLE_COLUMN.") AS num_articles");
	}

	/**
	 * Find the tag by url
	 * @param  string $url url of the tag
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findByUrl($url) {
		return $this->database->table("tag")->where(self::URL_COLUMN, $url)->fetch();
	}

	/**
	 * Check duplicity of existing tag
	 * @param  string $name new name for a tag
	 * @param  number $id   id of the tag
	 * @return number       number of other occurences, idealy 0 if there is no duplicity
	 */
	public function checkForDuplicatesWithId($name, $id) {
		$url = Strings::webalize($name);
		return $this->database->table("tag")->where(self::URL_COLUMN, $url)->where(self::ID_COLUMN." != ?", $id)->count(self::ID_COLUMN);
	}

	/**
	 * Check duplicity of a new tag
	 * @param  string $name new name for a tag
	 * @return number       number of other occurences, idealy 0 if there is no duplicity
	 */
	public function checkForDuplicates($name) {
		$url = Strings::webalize($name);
		return $this->database->table("tag")->where(self::URL_COLUMN, $url)->count(self::ID_COLUMN);
	}

	/**
	 * Find the last tag, the one with the highest id
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findLast() {
		return $this->database->table("tag")->order(self::ID_COLUMN." DESC")->fetch();
	}

	/**
	 * Find all tags for the article
	 * @param  number $article id of the article
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllArticleTags($article) {
		return $this->database->table("article_tag")->where(self::AT_ARTICLE_COLUMN, $article)->order("tag.".self::NAME_COLUMN);
	}

	/**
	 * Find all tags that are not assigned to the article
	 * @param  number $article id of the article
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllArticleNonTags($article) {
		return $this->database->table("tag")->where(self::ID_COLUMN." NOT",
			$this->database->table("article_tag")->where(self::AT_ARTICLE_COLUMN, $article)->group(self::AT_TAG_COLUMN)->select(self::AT_TAG_COLUMN)
		)->order(self::NAME_COLUMN);
	}

	/**
	 * Find all articles assigned to the tag
	 * @param  number $tag if of the etag
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllTagArticles($tag) {
		return $this->database->table("article_tag")->where(self::AT_TAG_COLUMN, $tag)->order("article.".Article::DATE_COLUMN);
	}

	/**
	 * Check if given article is already assigned tp the given tag
	 * @param  number  $article id of the article
	 * @param  number  $tag     id of the tag
	 * @return boolean          true if the article is having the tag, false otherwise
	 */
	public function isArticleHavingTag($article, $tag) {
		return $this->database->table("article_tag")->where(self::AT_TAG_COLUMN, $tag)->where(self::AT_ARTICLE_COLUMN, $article)->count() > 0;
	}

	/**
	 * Add the article to the tag
	 * @param number $article id of the article
	 * @param number $tag     id of the tag
	 */
	public function addTagToArticle($article, $tag) {
		$this->database->table("article_tag")->insert(array(
			self::AT_ARTICLE_COLUMN => $article, self::AT_TAG_COLUMN => $tag
		));
	}

	/**
	 * Delete the tag from the article
	 * @param  number $article id of the article
	 * @param  number $tag     id of the tag
	 * @return number          number of deleted rows, idealy 1, when there is an error then 0
	 */
	public function deleteTagFromArticle($article, $tag) {
		return $this->database->table("article_tag")->where(array(
			self::AT_ARTICLE_COLUMN => $article, self::AT_TAG_COLUMN => $tag
		))->delete();
	}

	/**
	 * Insert a new tag
	 * @param  string $name name of the tag
	 * @return number       id of the newly inserted row
	 */
	public function insert($name) {
		return $this->database->table("tag")->insert(array(
			self::NAME_COLUMN => $name, self::URL_COLUMN => Strings::webalize($name)
		));
	}

	/**
	 * Update the tag
	 * @param  string $name new name for the tag
	 * @param  number $id   id of the tag
	 */
	public function update($name, $id) {
		$this->database->table("tag")->where(self::ID_COLUMN, $id)->update(array(
			self::NAME_COLUMN => $name, self::URL_COLUMN => Strings::webalize($name)
		));
	}

	/**
	 * Delete the tag and also delete it from the articles
	 * @param  number $id id of the tag
	 */
	public function delete($id) {
		$this->database->table("article_tag")->where(self::AT_TAG_COLUMN, $id)->delete();
		$this->database->table("tag")->where(self::ID_COLUMN, $id)->delete();
	}

}
