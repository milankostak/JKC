<?php

namespace App\Model;

use Nette;

class Rank extends Nette\LegacyObject {

	/** @var Nette\Database\Context */
	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * Find the 15 newest articles
	 * @return Nette\Database\Table\Selection
	 */
	public function findNewest() {
		return $this->database->table("article")->order(Article::DATE_COLUMN." DESC")->limit("15")
				->where(Article::DRAFT_COLUMN, 0)->where(Article::DATE_COLUMN." < ?", time());
	}

	/**
	 * Find the 15 most visited articles
	 * @return Nette\Database\Table\Selection
	 */
	public function findMostVisited() {
		return $this->database->table("article")->order(Article::VIEWS_COLUMN." DESC")->limit("15")
				->where(Article::DRAFT_COLUMN, 0)->where(Article::DATE_COLUMN." < ?", time());
	}

	/**
	 * Find the 15 most commented articles
	 * @return Nette\Database\ResultSet
	 */
	public function findMostCommented() {
		$article_date = "article.".Article::DATE_COLUMN;
		$article_id_article = "article.".Article::ID_COLUMN;
		$editor_name = "editor.".Editor::NAME_COLUMN;
		$num_comments = "num_comments";

		return $this->database->table("article")->group(Article::ID_COLUMN)->order("$num_comments DESC, $article_date")->limit("15")
				->where(Article::DRAFT_COLUMN, 0)->where("$article_date < ?", time())
				->select("$article_id_article, ".Article::TITLE_COLUMN.", $article_date, $editor_name, COUNT(:comment.id_article) AS $num_comments");
	}

}
