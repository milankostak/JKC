<?php

namespace App\Presenters\Utils;

interface IFormUtils {

	function manageUidToken($form, $tokenName);

	function recoverData($form);

	function recoverInputs($values);

	function makeBootstrapForm($form);

	function createOkCancelForm($form, $_this, $cancelAction, $okAction);

	function addFormProtection($form);
}
