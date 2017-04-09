<?php

namespace App\Model;

use Nette;

class Editor extends Nette\Object {

	const ID_COLUMN = "id_editor";
	const LOGIN_COLUMN = "login";
	const PASSWORD_COLUMN = "password";
	const NAME_COLUMN = "name";
	const ADMIN_COLUMN = "admin";

	/** @var Nette\Database\Context */
	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * Hash password using bcrypt
	 * @param  string $password plain password
	 * @return string           hashed password
	 */
	private function hash($password) {
		return password_hash($password, PASSWORD_BCRYPT, array("cost" => 11));
	}

	/**
	 * Return all editors
	 * @return Nette\Database\Table\Selection
	 */
	public function findAll() {
		return $this->database->table("editor");
	}

	/**
	 * Return last created editor, the one with the highest id
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findLast() {
		return $this->database->table("editor")->order(self::ID_COLUMN." DESC")->fetch();
	}

	/**
	 * Find editor by id
	 * @param  number $id id of editor
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findById($id) {
		return $this->database->table("editor")->get($id);
	}

	/**
	 * Find editor by login
	 * @param  string $login login of editor
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function findByLogin($login) {
		return $this->database->table("editor")->where(self::LOGIN_COLUMN, $login)->fetch();
	}

	/**
	 * Check duplicity of login
	 * @param  string $login new login for editor
	 * @param  number $id   id of editor
	 * @return number       number of other occurences, idealy 0 if there is no duplicity
	 */
	public function checkForDuplicatesWithId($login, $id) {
		return $this->database->table("editor")->where(self::LOGIN_COLUMN, $login)->where(self::ID_COLUMN." != ?", $id)->count(self::ID_COLUMN);
	}

	/**
	 * Check duplicity of new editor
	 * @param  string $login new login for editor
	 * @return number        number of other occurences, idealy 0 if there is no duplicity
	 */
	public function checkForDuplicates($login) {
		return $this->database->table("editor")->where(self::LOGIN_COLUMN, $login)->count(self::ID_COLUMN);
	}

	/**
	 * Return password of editor by id
	 * @param  number $id id of editor
	 * @return string     password
	 */
	public function getPassword($id) {
		return $this->findById($id)->password;
	}

	/**
	 * Change password
	 * @param  string $password new password
	 * @param  number $id       id of editor
	 */
	public function updatePassword($password, $id) {
		$newpass = $this->hash($password);
		$this->database->table("editor")->where(self::ID_COLUMN, $id)->update(array(self::PASSWORD_COLUMN => $newpass));
	}

	/**
	 * Change login and name
	 * @param  array $values array of values
	 * @param  number $id    id of editor
	 */
	public function updateNameLogin($values, $id) {
		$this->database->table("editor")->where(self::ID_COLUMN, $id)->update(array(
			self::LOGIN_COLUMN => $values->login, self::NAME_COLUMN => $values->name
		));
	}

	/**
	 * Create new editor
	 * @param  array $values array of values
	 */
	public function insert($values) {
		$newpass = $this->hash($values->password1);
		$this->database->table("editor")->insert(array(
			self::LOGIN_COLUMN => $values->login, self::PASSWORD_COLUMN => $newpass,
			self::NAME_COLUMN => $values->name, self::ADMIN_COLUMN => $values->admin
		));
	}

	/**
	 * Update editor
	 * @param  array $values array of values
	 * @param  number $id    id of editor
	 */
	public function update($values, $id) {
		$this->database->table("editor")->where(self::ID_COLUMN, $id)->update(
			array(self::LOGIN_COLUMN => $values->login, self::NAME_COLUMN => $values->name, self::ADMIN_COLUMN => $values->admin)
		);
	}

	/**
	 * Delete editor
	 * @param  number $id id of editor
	 */
	public function delete($id) {
		$this->database->table("article")->where(self::ID_COLUMN, $id)->update(array(self::ID_COLUMN => NULL));
		$this->database->table("editor")->where(self::ID_COLUMN, $id)->delete();
	}

}
