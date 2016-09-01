<?php

namespace App\Presenters\Utils;

interface IFlashMessages {

	function flashMessageSuccess($message);

	function flashMessageError($message);

	function flashMessageAuthentification($message);

	function savingErrorFlashMessage();

}
