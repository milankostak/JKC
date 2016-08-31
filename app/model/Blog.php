<?php

namespace App\Model;

use Nette;

class Blog extends Nette\Object {

	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * Get all blog info
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function getBlogInfo() {
		return $this->getRow()->fetch();
	}

	/**
	 * Edit all basic blog info
	 * @param  array $values
	 */
	public function editBlogInfo($values) {
		$ga = ($values->ga == "") ? NULL : $values->ga;
		$this->getRow()->update(array(
			"name" => $values->name, "sub_name" => $values->sub_name,
			"posts_per_page" => $values->posts_per_page, "number_last_posts" => $values->number_last_posts,
			"number_rss_articles" => $values->number_rss_articles, "number_rss_comments" => $values->number_rss_comments,
			"ga" => $ga
		));
	}

	/**
	 * Edit top box
	 * @param  string $value new value of top box
	 */
	public function editTopBox($value) {
		$this->getRow()->update(array("top_box" => $value));
	}

	/**
	 * Edit bottom box
	 * @param  string $value new value of bottom box
	 */
	public function editBottomBox($value) {
		$this->getRow()->update(array("bottom_box" => $value));
	}

	/**
	 * Basic query for getting data
	 * @return Nette\Database\Table\Selection
	 */
	private function getRow() {
		return $this->database->table("blog")->where("id_blog", 1);
	}

}
