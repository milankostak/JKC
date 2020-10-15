<?php

namespace App\Model;

use Nette;

class Poll extends Nette\LegacyObject {

	const ID_COLUMN = "id_poll";
	const QUESTION_COLUMN = "question";
	const DATE_COLUMN = "date";

	/** @var Nette\Database\Context */
	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * Find poll by id
	 * @param  number $id id of poll
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findById($id) {
		return $this->database->table("poll")->get($id);
	}

	/**
	 * Find option by id
	 * @param  number $id id of option
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findOptionById($id) {
		return $this->database->table("poll_option")->get($id);
	}

	/**
	 * Find last poll, the one with the highest id
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findLast() {
		return $this->database->table("poll")->order(self::ID_COLUMN." DESC")->fetch();
	}

	/**
	 * Find all polls, reindexed for usage in html select element
	 * @return array
	 */
	public function findAllPollsAsArray() {
		$rows = $this->database->table("poll")->order(self::DATE_COLUMN." DESC");
		$w = [];
		foreach ($rows as $row) {
			$w[$row->id_poll] = $row->question;
		}
		return $w;
	}

	/**
	 * Find all poll options
	 * @param  number $id id of poll
	 * @return Nette\Database\Table\Selection
	 */
	public function findAllPollOptions($id) {
		return $this->database->table("poll_option")->where(self::ID_COLUMN, $id)->order(Option::ID_COLUMN);
	}

	/**
	 * Find all poll options with sum votes by poll id
	 * @param  number $id id of poll
	 * @return Nette\Database\Table\Selection
	 */
	public function findPollSumVotes($id) {
		return $this->database->table("poll_option")->where(self::ID_COLUMN, $id)->sum(Option::VOTES_COLUMN);
	}

	/**
	 * Find all polls with sum of votes and number of assigned articles
	 * @return Nette\Database\ResultSet
	 */
	public function findAllWithCount() {
		return $this->database->query("
			SELECT p.*, SUM( o.votes ) AS num_votes, (
				SELECT count( a.id_article ) AS num_articles
				FROM poll pp
				INNER JOIN article a ON a.id_poll = pp.id_poll
				WHERE p.id_poll = pp.id_poll
				) AS num_articles
			FROM poll_option o
			RIGHT JOIN poll p ON o.id_poll = p.id_poll
			GROUP BY p.id_poll
			ORDER BY p.question ASC 
		");
	}

	/**
	 * Manage vote for poll option, call db procedure
	 * @param  number $option id of option
	 * @return number         new number of votes for this option
	 */
	public function vote($option) {
		return $this->database->query("CALL `increaseVotes` (?, @`votes_result`)", $option);
	}

	/**
	 * Insert new poll
	 * @param  string $question name of question
	 * @return number           id of newly created poll
	 */
	public function insert($question) {
		return $this->database->table("poll")->insert(array(
			self::QUESTION_COLUMN => $question, self::DATE_COLUMN => time()
		));
	}

	/**
	 * Update question of poll
	 * @param  string $question new text of question
	 * @param  number $id       if of poll
	 */
	public function update($question, $id) {
		$this->database->table("poll")->where(self::ID_COLUMN, $id)->update(array(
			self::QUESTION_COLUMN => $question
		));
	}

	/**
	 * Delete poll, its options and assignments in articles
	 * @param  number $id id of poll
	 */
	public function delete($id) {
		$this->database->table("article")->where(self::ID_COLUMN, $id)->update(array(
			self::ID_COLUMN => NULL
		));
		$this->database->table("poll_option")->where(self::ID_COLUMN, $id)->delete();
		$this->database->table("poll")->where(self::ID_COLUMN, $id)->delete();
	}
}
