<?php

namespace App\Model;

use Nette;

class Option extends Nette\LegacyObject {

	const ID_COLUMN = "id_option";
	const ANSWER_COLUMN = "answer";
	const VOTES_COLUMN = "votes";
	const ID_POLL_COLUMN = "id_poll";

	/** @var Nette\Database\Context */
	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * Find option by id
	 * @param  number $id id of option
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findById($id) {
		return $this->database->table("poll_option")->get($id);
	}

	/**
	 * Find last option, the one with the highest id
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findLast() {
		return $this->database->table("poll_option")->order(self::ID_COLUMN." DESC")->fetch();
	}

	/**
	 * Insert new option
	 * @param  string $answer text of answer for question of poll
	 * @param  number $poll   id of poll
	 * @return number         id of newly created option
	 */
	public function insert($answer, $poll) {
		return $this->database->table("poll_option")->insert(array(
			self::ANSWER_COLUMN => $answer, self::VOTES_COLUMN => 0, self::ID_POLL_COLUMN => $poll
		));
	}

	/**
	 * Update answer of option
	 * @param  string $answer new text of answer
	 * @param  number $id     id of option
	 */
	public function update($answer, $id) {
		$this->database->table("poll_option")->where(self::ID_COLUMN, $id)->update(array(
			self::ANSWER_COLUMN => $answer
		));
	}

	/**
	 * Delete option
	 * @param  number $id id of option
	 */
	public function delete($id) {
		$this->database->table("poll_option")->where(self::ID_COLUMN, $id)->delete();
	}
}