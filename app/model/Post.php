<?php

namespace App\Model;

use Nette;

class Post extends Nette\Object {

	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * Find all basic articles - means all published, shown on the web - is not draft and publish date is not in future
	 * @return Nette\Database\Table\Selection
	 */
	public function findBasicAll() {
		return $this->database->table("article")->where(Article::DRAFT_COLUMN, 0)->where(Article::DATE_COLUMN." < ?", time())
				->order(Article::DATE_COLUMN." DESC");
	}

	/**
	 * Find only one basic post
	 * @param  string $url url of post
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findById($url) {
		return $this->findBasicAll()->where(Article::URL_COLUMN, $url)->fetch();
	}

	/**
	 * Find all posts within time interval for archive
	 * @param  number $start timestamp
	 * @param  number $end   timestamp
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllPostsByTimeInterval($start, $end) {
		return $this->findBasicAll()->where(Article::DATE_COLUMN." >= ?", $start)->where(Article::DATE_COLUMN." <= ?", $end);
	}

	/**
	 * Find last posts for sidebar
	 * @param  number $limit limit for number of posts
	 * @return Nette\Database\Table\Selection
	 */
	public function findLastPosts($limit) {
		return $this->findBasicAll()->limit($limit);
	}

	/**
	 * Get count of published posts for pagination
	 * @return number
	 */
	public function getCount() {
		return $this->findBasicAll()->count(Article::ID_COLUMN);
	}

	/**
	 * Find poll in a post
	 * @param  string $url url of post
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findPostPoll($url) {
		return $this->database->table("article")->where(Article::URL_COLUMN, $url)->select("poll.*")->fetch();
	}

	/**
	 * Call inner procedure to increase number of views of a post
	 * @param  string $url url of post
	 * @return number      current number of views
	 */
	public function increaseViews($url) {
		return $this->database->query("CALL `increaseViews` (?, @`views_result`)", $url);
	}

	/**
	 * Get count of posts for a tag for pagination
	 * @param  string $url url of tag
	 * @return number      number of posts
	 */
	public function getArticleCountPerTag($url) {
		return $this->database->table("tag")->where("tag.".Tag::URL_COLUMN, $url)->where(Article::DRAFT_COLUMN, 0)
				->where(":article_tag.article.".Article::DATE_COLUMN." < ?", time())->count(":article_tag.".Tag::AT_ARTICLE_COLUMN);
	}

	/* !!!!!
	 * Problem with following 3 queries is with matching data with posts.
	 * It is needed to have number of comments for each post and every tag for each post.
	 * That is the reason why it is done this way.
	 */

	/**
	 * Find all posts for pagionation wihtin given limit and offset
	 * @param  number $length limit
	 * @param  number $offset offset
	 * @return Nette\Database\Table\ResultSet
	 */
	public function findAllByLimitAndOffset($length, $offset) {
		return $this->database->query("
			SELECT a.title, a.anotation, a.date, a.url, a.comments, e.name,
				(SELECT count(*) FROM comment
					WHERE (id_article = a.id_article) AND (deleted = 0)
				) AS comment_count,
				(SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR '=')
					FROM tag t
					INNER JOIN article_tag at ON t.id_tag = at.id_tag
					WHERE at.id_article = a.id_article
				) AS tags
			FROM article a
			LEFT JOIN editor e ON a.id_editor = e.id_editor
			LEFT JOIN comment c ON a.id_article = c.id_article
			WHERE (a.date < ?) AND (a.draft = 0)
			GROUP BY a.id_article
			ORDER BY a.date DESC
			LIMIT ?, ?
		", time(), $offset, $length);
	}

	/**
	 * Find all posts in a tag for pagionation within giver limit and offset
	 * @param  string $url   url of tag
	 * @param  number $length limit
	 * @param  number $offset offset
	 * @return Nette\Database\Table\ResultSet
	 */
	public function findAllPostsByTagName($url, $length, $offset) {
		return $this->database->query("
			SELECT a.title, a.anotation, a.date, a.url, a.comments, e.name,
				(SELECT count(*) FROM comment
					WHERE (id_article = a.id_article) AND (deleted = 0)
				) AS comment_count,
				(SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR '=')
					FROM tag t
					INNER JOIN article_tag at ON t.id_tag = at.id_tag
					WHERE at.id_article = a.id_article
				) AS tags
			FROM article a
			LEFT JOIN editor e ON a.id_editor = e.id_editor
			LEFT JOIN article_tag at ON at.id_article = a.id_article
			WHERE (at.id_tag = (SELECT id_tag FROM tag WHERE url = ?)) AND (a.date < ?) AND (draft = 0)
			GROUP BY a.id_article
			ORDER BY a.date DESC
			LIMIT ?, ?
		", $url, time(), $offset, $length);
	}

	/**
	 * Find all info of a post
	 * @param  string $url url of post
	 * @return Nette\Database\Table\ResultSet
	 */
	public function findByIdWithCommentsCountAndTags($url) {
		return $this->database->query("
			SELECT a.title, a.text, a.date, a.url, a.social, a.views, a.comments, e.name,
				(SELECT count(*) FROM comment
					WHERE (id_article = a.id_article) AND (deleted = 0)
				) AS comment_count,
				(SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR '=')
					FROM tag t
					INNER JOIN article_tag at ON t.id_tag = at.id_tag
					WHERE at.id_article = a.id_article
				) AS tags
			FROM article a
			LEFT JOIN editor e ON a.id_editor = e.id_editor
			LEFT JOIN comment c ON a.id_article = c.id_article
			WHERE (a.url = ?) AND (a.date < ?) AND (draft = 0)
			GROUP BY a.id_article
		", $url, time())->fetch();
	}
}
