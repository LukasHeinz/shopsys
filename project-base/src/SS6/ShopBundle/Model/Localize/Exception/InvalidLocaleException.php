<?php

namespace SS6\ShopBundle\Model\Localize\Exception;

use Exception;

class InvalidLocaleException extends Exception implements LocalizeException {

	/**
	 * @param string $message
	 * @param \Exception $previous
	 */
	public function __construct($message, Exception $previous = null) {
		parent::__construct($message, 0, $previous);
	}

}
