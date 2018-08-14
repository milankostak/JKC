<?php

namespace App\Presenters;

use Nette\Application\UI\Presenter;
use App\Presenters\Utils\FlashMessages;
use App\Presenters\Utils\FormUtils;

abstract class BasePresenter extends Presenter {

	/** @var App\Presenters\Utils\IFlashMessages */
	protected $flashMessages;

	/** @var App\Presenters\Utils\IFormUtils */
	protected $formUtils;

	protected function startup() {
		parent::startup();
		$this->flashMessages = new FlashMessages($this);
		$this->formUtils = new FormUtils($this);
	}

	/**
	 * Verify password
	 * @param  string $pass plain string to verify
	 * @param  string $hash hashed password
	 * @return boolen       true if passwords match, false otherwise
	 */
	protected function passwordVerify($pass, $hash) {
		return password_verify($pass, $hash);
	}

	/**
	 * Initialize custom latte filters
	 */
	protected function beforeRender() {
		/** Return czech month name according to its number */
		$this->template->addFilter("numericToStringMonth", function ($numericMonth) {
			switch ($numericMonth) {
				case 1: return "Leden";
				case 2: return "Únor";
				case 3: return "Březen";
				case 4: return "Duben";
				case 5: return "Květen";
				case 6: return "Červen";
				case 7: return "Červenec";
				case 8: return "Srpen";
				case 9: return "Září";
				case 10: return "Říjen";
				case 11: return "Listopad";
				case 12: return "Prosinec";
			}
		});
	}
}
