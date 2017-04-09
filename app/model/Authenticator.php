<?php

namespace App\Model;

use Nette;
use Nette\Security as NS;

class Authenticator extends Nette\Object implements NS\IAuthenticator {

	/** @var Nette\Database\Context */
	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	function authenticate(array $credentials) {
		list($username, $password) = $credentials;
		$row = $this->database->table("editor")->where(Editor::LOGIN_COLUMN, $username)->fetch();

		if (!$row || !password_verify($password, $row->password)) {
			throw new NS\AuthenticationException("Neplatné uživatelské jméno nebo heslo.");
		}

		$arr = $row->toArray();
		unset($arr["password"]);
		return new NS\Identity($row->id_editor, array("Admin" => $row->admin), $arr);
	}
}
