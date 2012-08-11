<?php

class Message {

	const SEVERITY_SUCCESS = 0;
	const SEVERITY_INFO = 1;
	const SEVERITY_WARN = 2;
	const SEVERITY_ERROR = 3;

	private $message, $severity;

	function __construct($message, $severity = self::SEVERITY_INFO) {
		$this->message = $message;
		$this->severity = $severity;
		if ($severity < 0 || $severity > 3) {
			throw new InvalidArgumentException("severity out of range");
		}
	}

	function getMessage() {
		return $this->message;
	}

	function getSeverity() {
		return $this->severity;
	}

	function getClass() {
		switch ($this->severity) {
		case self::SEVERITY_SUCCESS:
			return 'message-success';
		case self::SEVERITY_INFO:
			return 'message-info';
		case self::SEVERITY_WARN:
			return 'message-warn';
		case self::SEVERITY_ERROR:
			return 'message-error';
		}
	}
}

?>