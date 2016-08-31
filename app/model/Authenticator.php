<?php

namespace App\Model;

use Nette;
use Nette\Security as NS;

class Authenticator extends Nette\Object implements NS\IAuthenticator {

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
		require "password.php";
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
/*
echo password_hash("rasmuslerdorf", PASSWORD_BCRYPT, array("cost" => 11))
*/